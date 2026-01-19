<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" 
      :class="{ 'dark': darkMode }" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatApp - Modern Messaging</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 antialiased" x-data="chatApp()">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar: Conversations List -->
        <div class="w-full sm:w-96 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col"
             :class="{ 'hidden sm:flex': selectedConversation }">
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Messages</h1>
                <div class="flex items-center gap-2">
                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
                    <!-- New Chat Button -->
                    <button @click="showNewChatModal = true" 
                            class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Search conversations..." 
                       class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Conversations List -->
            <div class="flex-1 overflow-y-auto">
                <template x-for="conversation in filteredConversations" :key="conversation.id">
                    <div @click="selectConversation(conversation)" 
                         class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition border-b border-gray-100 dark:border-gray-700"
                         :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedConversation?.id === conversation.id }">
                        
                        <!-- Avatar -->
                        <div class="relative flex-shrink-0">
                            <img :src="conversation.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(conversation.name)" 
                                 :alt="conversation.name"
                                 class="w-12 h-12 rounded-full object-cover">
                            <span x-show="conversation.is_online" 
                                  class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                        </div>

                        <!-- Conversation Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-medium text-gray-900 dark:text-white truncate" x-text="conversation.name"></h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(conversation.updated_at)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="conversation.last_message?.content || 'No messages yet'"></p>
                                <span x-show="conversation.unread_count > 0" 
                                      class="ml-2 px-2 py-0.5 text-xs font-semibold text-white bg-blue-600 rounded-full"
                                      x-text="conversation.unread_count"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="conversations.length === 0" class="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p>No conversations yet</p>
                    <button @click="showNewChatModal = true" class="mt-2 text-blue-600 hover:text-blue-700">Start a new chat</button>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col bg-white dark:bg-gray-800" x-show="selectedConversation" x-cloak>
            <!-- Chat Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <!-- Back Button (Mobile) -->
                    <button @click="selectedConversation = null" class="sm:hidden p-2 -ml-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    
                    <img :src="selectedConversation?.avatar" 
                         :alt="selectedConversation?.name"
                         class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white" x-text="selectedConversation?.name"></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="typingUsers.length > 0 ? typingUsers.join(', ') + ' typing...' : (selectedConversation?.is_online ? 'Online' : 'Offline')"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Audio Call -->
                    <button @click="initiateCall('audio')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </button>
                    <!-- Video Call -->
                    <button @click="initiateCall('video')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    <!-- More Options -->
                    <button @click="showChatOptions = !showChatOptions" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900" 
                 x-ref="messagesContainer"
                 @scroll.debounce="handleScroll">
                
                <template x-for="message in messages" :key="message.id">
                    <div class="flex" :class="message.user_id === currentUserId ? 'justify-end' : 'justify-start'">
                        <div class="flex gap-2 max-w-[70%]" :class="message.user_id === currentUserId ? 'flex-row-reverse' : 'flex-row'">
                            <!-- User Avatar -->
                            <img :src="getUserAvatar(message.user_id)" 
                                 class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            
                            <!-- Message Content -->
                            <div>
                                <!-- Reply Reference -->
                                <div x-show="message.reply_to" class="mb-1 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400">
                                    <p>Replying to...</p>
                                </div>

                                <!-- Message Bubble -->
                                <div class="rounded-2xl px-4 py-2 shadow-sm"
                                     :class="message.user_id === currentUserId ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white'">
                                    
                                    <!-- Text Content -->
                                    <p x-show="message.type === 'text'" x-html="formatMessage(message.content)"></p>
                                    
                                    <!-- Image Attachment -->
                                    <template x-if="message.attachments?.length > 0 && message.attachments[0].type === 'image'">
                                        <img :src="message.attachments[0].url" class="rounded-lg max-w-full h-auto">
                                    </template>

                                    <!-- Video Attachment -->
                                    <template x-if="message.attachments?.length > 0 && message.attachments[0].type === 'video'">
                                        <video controls class="rounded-lg max-w-full h-auto">
                                            <source :src="message.attachments[0].url">
                                        </video>
                                    </template>

                                    <!-- Audio Message -->
                                    <template x-if="message.attachments?.length > 0 && message.attachments[0].type === 'audio'">
                                        <audio controls class="w-full">
                                            <source :src="message.attachments[0].url">
                                        </audio>
                                    </template>

                                    <!-- Edited Indicator -->
                                    <span x-show="message.edited_at" class="text-xs opacity-75 ml-2">(edited)</span>
                                </div>

                                <!-- Message Meta -->
                                <div class="flex items-center gap-2 mt-1 px-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(message.created_at)"></span>
                                    
                                    <!-- Reactions -->
                                    <div x-show="message.reactions?.length > 0" class="flex gap-1">
                                        <template x-for="reaction in getUniqueReactions(message.reactions)" :key="reaction.emoji">
                                            <button @click="toggleReaction(message.id, reaction.emoji)"
                                                    class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-xs hover:bg-gray-200 dark:hover:bg-gray-600">
                                                <span x-text="reaction.emoji"></span>
                                                <span x-text="reaction.count"></span>
                                            </button>
                                        </template>
                                    </div>
                                    
                                    <!-- React Button -->
                                    <button @click="showEmojiPicker = message.id" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Message Input -->
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex items-end gap-2">
                    <!-- Attachment Button -->
                    <button @click="$refs.fileInput.click()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>
                    <input type="file" x-ref="fileInput" @change="handleFileSelect" class="hidden" multiple>

                    <!-- Message Input Field -->
                    <div class="flex-1">
                        <textarea x-model="messageInput"
                                  @keydown.enter.prevent="!$event.shiftKey && sendMessage()"
                                  @input="handleTyping"
                                  placeholder="Type a message..."
                                  rows="1"
                                  class="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>

                    <!-- Emoji Button -->
                    <button @click="showMainEmojiPicker = !showMainEmojiPicker" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>

                    <!-- Sticker Button -->
                    <button @click="showStickerPicker = !showStickerPicker" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </button>

                    <!-- Send Button -->
                    <button @click="sendMessage" 
                            :disabled="!messageInput.trim() && selectedFiles.length === 0"
                            class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>

                <!-- File Preview -->
                <div x-show="selectedFiles.length > 0" class="mt-2 flex gap-2 flex-wrap">
                    <template x-for="(file, index) in selectedFiles" :key="index">
                        <div class="relative inline-block">
                            <img x-show="file.type.startsWith('image/')" :src="file.preview" class="w-20 h-20 object-cover rounded-lg">
                            <div x-show="!file.type.startsWith('image/')" class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <span class="text-xs" x-text="file.name.split('.').pop()"></span>
                            </div>
                            <button @click="selectedFiles.splice(index, 1)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Empty State (No conversation selected) -->
        <div x-show="!selectedConversation" class="hidden sm:flex flex-1 items-center justify-center bg-gray-50 dark:bg-gray-900">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-xl font-semibold mb-2">Select a conversation</h3>
                <p>Choose a conversation from the list to start messaging</p>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div x-show="showNewChatModal" 
         @click.self="showNewChatModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">New Conversation</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select x-model="newChat.type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="direct">Direct Message</option>
                        <option value="group">Group Chat</option>
                    </select>
                </div>

                <div x-show="newChat.type === 'group'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Group Name</label>
                    <input type="text" x-model="newChat.name" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Users</label>
                    <input type="text" 
                           x-model="userSearchQuery"
                           @input.debounce="searchUsers"
                           placeholder="Search by name or email..."
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    
                    <div x-show="searchResults.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg">
                        <template x-for="user in searchResults" :key="user.id">
                            <div @click="toggleUserSelection(user)" 
                                 class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                 :class="{ 'bg-blue-50 dark:bg-blue-900/20': newChat.participants.includes(user.id) }">
                                <img :src="user.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name)" 
                                     class="w-8 h-8 rounded-full">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="user.name"></p>
                                    <p class="text-xs text-gray-500" x-text="user.email"></p>
                                </div>
                                <svg x-show="newChat.participants.includes(user.id)" class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showNewChatModal = false" 
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                    <button @click="createConversation" 
                            :disabled="newChat.participants.length === 0"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        Create
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Emoji Picker (Simple) -->
    <div x-show="showMainEmojiPicker" 
         @click.outside="showMainEmojiPicker = false"
         class="absolute bottom-20 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 z-50 border border-gray-200 dark:border-gray-700"
         x-cloak>
        <div class="grid grid-cols-8 gap-2">
            <template x-for="emoji in commonEmojis">
                <button @click="messageInput += emoji; showMainEmojiPicker = false" 
                        class="text-2xl hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1"
                        x-text="emoji"></button>
            </template>
        </div>
    </div>

    <script>
        function chatApp() {
            return {
                currentUserId: {{ auth()->id() }},
                conversations: [],
                selectedConversation: null,
                messages: [],
                messageInput: '',
                searchQuery: '',
                showNewChatModal: false,
                showMainEmojiPicker: false,
                showStickerPicker: false,
                showChatOptions: false,
                selectedFiles: [],
                typingUsers: [],
                typingTimeout: null,
                newChat: {
                    type: 'direct',
                    name: '',
                    participants: []
                },
                userSearchQuery: '',
                searchResults: [],
                commonEmojis: ['😀', '😂', '😍', '🥰', '😊', '😎', '🤔', '😢', '😡', '👍', '👎', '❤️', '🔥', '✨', '🎉', '💯'],
                currentChannel: null,

                init() {
                    this.loadConversations();
                    this.setupEcho();
                    this.requestNotificationPermission();
                },

                async loadConversations() {
                    try {
                        const response = await axios.get('/conversations');
                        this.conversations = response.data;
                    } catch (error) {
                        console.error('Failed to load conversations:', error);
                    }
                },

                async selectConversation(conversation) {
                    this.selectedConversation = conversation;
                    await this.loadMessages(conversation.id);
                    this.markAsRead(conversation.id);
                    this.joinConversationChannel(conversation.id);
                },

                async loadMessages(conversationId) {
                    try {
                        const response = await axios.get(`/conversations/${conversationId}/messages`);
                        this.messages = response.data.data;
                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        console.error('Failed to load messages:', error);
                    }
                },

                async sendMessage() {
                    if (!this.messageInput.trim() && this.selectedFiles.length === 0) return;

                    const formData = new FormData();
                    formData.append('content', this.messageInput);
                    formData.append('type', this.selectedFiles.length > 0 ? 'image' : 'text');

                    this.selectedFiles.forEach((file, index) => {
                        formData.append(`attachments[${index}][file]`, file);
                    });

                    try {
                        const response = await axios.post(`/conversations/${this.selectedConversation.id}/messages`, formData);
                        this.messages.push(response.data);
                        this.messageInput = '';
                        this.selectedFiles = [];
                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        console.error('Failed to send message:', error);
                    }
                },

                async createConversation() {
                    try {
                        const response = await axios.post('/conversations', {
                            type: this.newChat.type,
                            name: this.newChat.name,
                            participant_ids: this.newChat.participants
                        });
                        this.conversations.unshift(response.data);
                        this.showNewChatModal = false;
                        this.newChat = { type: 'direct', name: '', participants: [] };
                        this.selectConversation(response.data);
                    } catch (error) {
                        console.error('Failed to create conversation:', error);
                    }
                },

                async searchUsers() {
                    if (!this.userSearchQuery.trim()) {
                        this.searchResults = [];
                        return;
                    }

                    try {
                        const response = await axios.get('/users/search', {
                            params: { q: this.userSearchQuery }
                        });
                        this.searchResults = response.data;
                    } catch (error) {
                        console.error('Failed to search users:', error);
                    }
                },

                toggleUserSelection(user) {
                    const index = this.newChat.participants.indexOf(user.id);
                    if (index > -1) {
                        this.newChat.participants.splice(index, 1);
                    } else {
                        if (this.newChat.type === 'direct') {
                            this.newChat.participants = [user.id];
                        } else {
                            this.newChat.participants.push(user.id);
                        }
                    }
                },

                async handleFileSelect(event) {
                    const files = Array.from(event.target.files);
                    for (const file of files) {
                        if (file.type.startsWith('image/')) {
                            file.preview = URL.createObjectURL(file);
                        }
                        this.selectedFiles.push(file);
                    }
                },

                handleTyping() {
                    if (this.typingTimeout) clearTimeout(this.typingTimeout);
                    
                    axios.post(`/conversations/${this.selectedConversation.id}/typing`, { is_typing: true });
                    
                    this.typingTimeout = setTimeout(() => {
                        axios.post(`/conversations/${this.selectedConversation.id}/typing`, { is_typing: false });
                    }, 3000);
                },

                async toggleReaction(messageId, emoji) {
                    try {
                        await axios.post(`/messages/${messageId}/react`, { emoji });
                    } catch (error) {
                        console.error('Failed to react:', error);
                    }
                },

                async markAsRead(conversationId) {
                    try {
                        await axios.post(`/conversations/${conversationId}/read`);
                        const conv = this.conversations.find(c => c.id === conversationId);
                        if (conv) conv.unread_count = 0;
                    } catch (error) {
                        console.error('Failed to mark as read:', error);
                    }
                },

                async initiateCall(type) {
                    const participants = this.selectedConversation.type === 'direct' 
                        ? [this.selectedConversation.participants.find(p => p.id !== this.currentUserId).id]
                        : this.selectedConversation.participants.map(p => p.id).filter(id => id !== this.currentUserId);

                    try {
                        const response = await axios.post(`/conversations/${this.selectedConversation.id}/calls`, {
                            type,
                            participant_ids: participants
                        });
                        // WebRTC call initiation will be handled by the component
                        this.$dispatch('call-initiated', response.data);
                    } catch (error) {
                        console.error('Failed to initiate call:', error);
                    }
                },

                setupEcho() {
                    // Join user's private channel
                    window.Echo.private(`user.${this.currentUserId}`)
                        .listen('CallInitiated', (e) => {
                            this.handleIncomingCall(e.call);
                        });
                },

                joinConversationChannel(conversationId) {
                    // Leave previous channel if exists
                    if (this.currentChannel) {
                        window.Echo.leave(this.currentChannel);
                    }

                    // Join new channel
                    const channelName = `conversation.${conversationId}`;
                    this.currentChannel = channelName;
                    
                    window.Echo.private(channelName)
                        .listen('MessageSent', (e) => {
                            console.log('MessageSent event received:', e);
                            if (e.message.user_id !== this.currentUserId) {
                                this.messages.push(e.message);
                                this.$nextTick(() => this.scrollToBottom());
                                this.showNotification(e.message);
                            }
                        })
                        .listen('UserTyping', (e) => {
                            if (e.user_id !== this.currentUserId) {
                                if (e.is_typing) {
                                    if (!this.typingUsers.includes(e.user_name)) {
                                        this.typingUsers.push(e.user_name);
                                    }
                                } else {
                                    this.typingUsers = this.typingUsers.filter(name => name !== e.user_name);
                                }
                            }
                        })
                        .listen('MessageReacted', (e) => {
                            const message = this.messages.find(m => m.id === e.reaction.message_id);
                            if (message) {
                                if (!message.reactions) message.reactions = [];
                                message.reactions.push(e.reaction);
                            }
                        });
                    
                    console.log(`Joined channel: ${channelName}`);
                },

                requestNotificationPermission() {
                    if ('Notification' in window) {
                        if (Notification.permission === 'default') {
                            Notification.requestPermission().then(permission => {
                                if (permission === 'granted') {
                                    console.log('Notification permission granted');
                                    // Show a test notification
                                    new Notification('ChatApp', {
                                        body: 'You will now receive desktop notifications for new messages!',
                                        icon: '/favicon.ico'
                                    });
                                }
                            });
                        } else if (Notification.permission === 'granted') {
                            console.log('Notification permission already granted');
                        } else {
                            console.log('Notification permission denied');
                        }
                    }
                },

                showNotification(message) {
                    if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
                        const notification = new Notification(message.user.name, {
                            body: message.content || 'New message',
                            icon: message.user.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(message.user.name),
                            badge: '/favicon.ico',
                            tag: `message-${message.id}`,
                            requireInteraction: false
                        });
                        
                        notification.onclick = () => {
                            window.focus();
                            notification.close();
                        };
                        
                        // Auto close after 5 seconds
                        setTimeout(() => notification.close(), 5000);
                    }
                },

                handleIncomingCall(call) {
                    // Handle incoming call UI
                    alert(`Incoming ${call.type} call from conversation ${call.conversation_id}`);
                },

                scrollToBottom() {
                    const container = this.$refs.messagesContainer;
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                },

                formatTime(timestamp) {
                    const date = new Date(timestamp);
                    const now = new Date();
                    const diff = now - date;
                    
                    if (diff < 60000) return 'Just now';
                    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
                    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
                    return date.toLocaleDateString();
                },

                formatMessage(content) {
                    // Basic markdown-like formatting
                    return content
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/\n/g, '<br>');
                },

                getUserAvatar(userId) {
                    // Get avatar from conversations participants
                    const user = this.selectedConversation?.participants?.find(p => p.id === userId);
                    return user?.avatar_url || 'https://ui-avatars.com/api/?name=User';
                },

                getUniqueReactions(reactions) {
                    const grouped = {};
                    reactions?.forEach(r => {
                        if (!grouped[r.emoji]) {
                            grouped[r.emoji] = { emoji: r.emoji, count: 0 };
                        }
                        grouped[r.emoji].count++;
                    });
                    return Object.values(grouped);
                },

                get filteredConversations() {
                    if (!this.searchQuery) return this.conversations;
                    return this.conversations.filter(c => 
                        c.name.toLowerCase().includes(this.searchQuery.toLowerCase())
                    );
                }
            }
        }
    </script>
</body>
</html>

