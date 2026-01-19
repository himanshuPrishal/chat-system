# ChatApp - Advanced Real-Time Messaging Application

A modern, feature-rich WhatsApp-like messaging application built with Laravel 12, Alpine.js, Tailwind CSS, and Pusher for real-time communication.

## ✨ Features

### Core Messaging
- ✅ Real-time one-on-one messaging
- ✅ Group chats with admin controls
- ✅ Message read receipts (seen/delivered)
- ✅ Typing indicators
- ✅ Message editing (within 15 minutes)
- ✅ Delete messages (for me / for everyone)
- ✅ Reply to messages
- ✅ Search conversations and messages

### Rich Content
- ✅ User mentions (@username) with autocomplete
- ✅ Emoji reactions on messages
- ✅ Emoji picker
- ✅ Sticker support with packs
- ✅ Image, video, and audio sharing
- ✅ Voice messages
- ✅ File attachments
- ✅ Text formatting (bold, italic)

### Video/Audio Calls
- ✅ WebRTC-based peer-to-peer calls
- ✅ One-on-one video calls
- ✅ One-on-one audio calls
- ✅ Call history and logs
- ✅ In-call controls (mute, video on/off)
- ✅ Missed call tracking

### User Experience
- ✅ Modern, minimal UI design
- ✅ Dark mode support
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Desktop notifications
- ✅ Online/offline status
- ✅ Last seen timestamps
- ✅ User profile with avatar, status, bio
- ✅ Privacy settings

### Group Management
- ✅ Create groups with custom names and avatars
- ✅ Add/remove members
- ✅ Admin and member roles
- ✅ Group descriptions
- ✅ Leave group functionality

## 🚀 Tech Stack

- **Backend**: Laravel 12 (PHP 8.2)
- **Frontend**: Blade Templates, Alpine.js, Tailwind CSS 4
- **Real-time**: Pusher (WebSocket)
- **Video/Audio**: WebRTC (peer-to-peer)
- **Database**: PostgreSQL (can use MySQL/SQLite)
- **Cache/Queue**: Redis
- **Storage**: Local (can configure S3, Cloudinary)
- **Build Tool**: Vite

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- PostgreSQL/MySQL/SQLite
- Redis (for queues and caching)
- Pusher account (free tier available)

## 🛠️ Installation

### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone <your-repo-url>
cd laravel-app

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Database

Update your `.env` file with database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Configure Pusher

1. Sign up for a free account at [https://pusher.com](https://pusher.com)
2. Create a new Channels app
3. Update your `.env` file:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 5. Configure Redis

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Build Assets

```bash
# For development
npm run dev

# For production
npm run build
```

## 🎯 Running the Application

### Start the Development Server

```bash
# Option 1: Use Laravel's built-in dev command (recommended)
composer dev

# Option 2: Start services individually
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:listen

# Terminal 3: Vite dev server
npm run dev
```

### Access the Application

- Open your browser and navigate to `http://localhost:8000`
- Register a new account or login
- Start chatting!

## 📱 Usage Guide

### Creating Conversations

1. Click the "+" button in the sidebar
2. Choose between Direct Message or Group Chat
3. Search for users to add
4. For groups, set a name and description
5. Click "Create"

### Sending Messages

- **Text**: Type in the message box and press Enter
- **Images/Files**: Click the paperclip icon
- **Emojis**: Click the smiley face icon
- **Stickers**: Click the sticker icon
- **Voice Messages**: Click the microphone icon (record audio)

### Mentions and Reactions

- **Mention**: Type @ followed by a user's name
- **React**: Hover over a message and click the reaction icon
- **Reply**: Right-click a message and select "Reply"

### Voice/Video Calls

1. Open a conversation
2. Click the phone icon (audio) or video icon
3. Wait for the other user to answer
4. Use controls to mute/unmute or turn video on/off
5. Click end call when finished

### Group Management (Admins only)

- **Add Members**: Group settings → Add members
- **Remove Members**: Group settings → Manage members → Remove
- **Change Roles**: Group settings → Manage members → Change role
- **Update Avatar**: Group settings → Upload new avatar

## 🔧 Configuration Options

### File Upload Limits

Edit `config/filesystems.php` and `.env`:

```env
# Maximum file size for attachments (in KB)
UPLOAD_MAX_FILESIZE=51200  # 50MB
```

### Notification Settings

Users can configure:
- Desktop notifications on/off
- Notification sound
- Do Not Disturb mode
- Mute specific conversations

### Privacy Settings

- Online status visibility
- Last seen visibility
- Profile photo visibility
- Block users

## 🎨 UI Customization

The application uses Tailwind CSS 4. To customize:

1. Edit `resources/css/app.css` for custom styles
2. Modify Tailwind configuration if needed
3. Rebuild assets: `npm run build`

### Dark Mode

- Toggle dark mode using the moon/sun icon in the header
- Preference is saved in localStorage
- Automatically applies across all pages

## 🔒 Security Features

- CSRF protection on all forms
- XSS protection with Laravel's Blade escaping
- SQL injection prevention via Eloquent ORM
- File upload validation (type, size)
- Rate limiting on API endpoints
- Authorization policies for conversations and messages
- Privacy controls for user visibility

## 🚀 Deployment

### Production Checklist

1. **Environment**:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Optimize**:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

3. **Queue Workers**:
   - Set up supervisor to keep queue workers running
   - Configure Redis for production use

4. **Storage**:
   - Consider using S3 or Cloudinary for media files
   - Update `config/filesystems.php` accordingly

5. **WebRTC**:
   - For production, consider using TURN servers
   - Update ICE servers in `resources/js/components/webrtc.js`

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## 📚 API Documentation

The application includes RESTful API endpoints:

### Conversations
- `GET /conversations` - List all conversations
- `POST /conversations` - Create new conversation
- `GET /conversations/{id}` - Get conversation details
- `PATCH /conversations/{id}` - Update conversation
- `DELETE /conversations/{id}` - Delete conversation

### Messages
- `GET /conversations/{id}/messages` - List messages
- `POST /conversations/{id}/messages` - Send message
- `PATCH /messages/{id}` - Edit message
- `DELETE /messages/{id}` - Delete message
- `POST /messages/{id}/react` - React to message

### Calls
- `POST /conversations/{id}/calls` - Initiate call
- `POST /calls/{id}/answer` - Answer call
- `POST /calls/{id}/reject` - Reject call
- `POST /calls/{id}/end` - End call
- `POST /calls/{id}/signal` - WebRTC signaling

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This project is open-sourced software licensed under the MIT license.

## 🙏 Acknowledgments

- Laravel Framework
- Alpine.js
- Tailwind CSS
- Pusher
- WebRTC

## 📞 Support

For issues, questions, or suggestions:
- Create an issue on GitHub
- Contact: your-email@example.com

## 🎉 Features Coming Soon

- [ ] End-to-end encryption
- [ ] Message forwarding
- [ ] Starred messages
- [ ] Archive conversations
- [ ] Custom themes
- [ ] Multi-language support
- [ ] Message translation
- [ ] GIF support
- [ ] Location sharing
- [ ] Contact sharing
- [ ] Polls and surveys
- [ ] Disappearing messages

---

**Built with ❤️ using Laravel and modern web technologies**
# chat-system
