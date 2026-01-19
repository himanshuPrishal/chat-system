// WebRTC Call Management Component
export function webrtcCall() {
    return {
        peerConnection: null,
        localStream: null,
        remoteStream: null,
        callLog: null,
        isCallActive: false,
        isVideoEnabled: true,
        isAudioEnabled: true,
        callType: 'video',
        callStartTime: null,
        callDuration: 0,
        durationInterval: null,

        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
        ],

        async initiateCall(conversationId, type, participantIds) {
            try {
                this.callType = type;
                
                // Get user media
                const constraints = {
                    audio: true,
                    video: type === 'video' ? {
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } : false
                };

                this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
                this.displayLocalStream();

                // Create call log
                const response = await axios.post(`/conversations/${conversationId}/calls`, {
                    type,
                    participant_ids: participantIds
                });

                this.callLog = response.data;
                this.isCallActive = true;
                this.startCallDuration();

                // Set up peer connection
                await this.setupPeerConnection(participantIds[0]);
                
                // Create and send offer
                const offer = await this.peerConnection.createOffer();
                await this.peerConnection.setLocalDescription(offer);

                // Send offer via signaling server
                await this.sendSignal('offer', offer, participantIds[0]);

            } catch (error) {
                console.error('Error initiating call:', error);
                alert('Failed to access camera/microphone. Please check permissions.');
                this.endCall();
            }
        },

        async answerCall(callLog, offer) {
            try {
                this.callLog = callLog;
                this.callType = callLog.type;
                this.isCallActive = true;

                // Get user media
                const constraints = {
                    audio: true,
                    video: callLog.type === 'video' ? {
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } : false
                };

                this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
                this.displayLocalStream();

                // Update call status
                await axios.post(`/calls/${callLog.id}/answer`);
                this.startCallDuration();

                // Set up peer connection
                await this.setupPeerConnection(callLog.initiated_by);

                // Set remote description
                await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer));

                // Create and send answer
                const answer = await this.peerConnection.createAnswer();
                await this.peerConnection.setLocalDescription(answer);

                await this.sendSignal('answer', answer, callLog.initiated_by);

            } catch (error) {
                console.error('Error answering call:', error);
                this.endCall();
            }
        },

        async rejectCall(callLog) {
            try {
                await axios.post(`/calls/${callLog.id}/reject`);
                this.isCallActive = false;
            } catch (error) {
                console.error('Error rejecting call:', error);
            }
        },

        async setupPeerConnection(targetUserId) {
            this.peerConnection = new RTCPeerConnection({
                iceServers: this.iceServers
            });

            // Add local stream tracks to peer connection
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                });
            }

            // Handle remote stream
            this.peerConnection.ontrack = (event) => {
                if (!this.remoteStream) {
                    this.remoteStream = new MediaStream();
                }
                this.remoteStream.addTrack(event.track);
                this.displayRemoteStream();
            };

            // Handle ICE candidates
            this.peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    this.sendSignal('ice-candidate', event.candidate, targetUserId);
                }
            };

            // Handle connection state changes
            this.peerConnection.onconnectionstatechange = () => {
                console.log('Connection state:', this.peerConnection.connectionState);
                
                if (this.peerConnection.connectionState === 'disconnected' || 
                    this.peerConnection.connectionState === 'failed' ||
                    this.peerConnection.connectionState === 'closed') {
                    this.endCall();
                }
            };
        },

        async sendSignal(type, data, targetUserId) {
            try {
                await axios.post(`/calls/${this.callLog.id}/signal`, {
                    type,
                    data,
                    target_user_id: targetUserId
                });
            } catch (error) {
                console.error('Error sending signal:', error);
            }
        },

        async handleSignal(signal) {
            try {
                if (signal.type === 'offer') {
                    await this.answerCall(this.callLog, signal.data);
                } else if (signal.type === 'answer') {
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription(signal.data));
                } else if (signal.type === 'ice-candidate') {
                    await this.peerConnection.addIceCandidate(new RTCIceCandidate(signal.data));
                }
            } catch (error) {
                console.error('Error handling signal:', error);
            }
        },

        toggleVideo() {
            if (this.localStream) {
                const videoTrack = this.localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    this.isVideoEnabled = videoTrack.enabled;
                }
            }
        },

        toggleAudio() {
            if (this.localStream) {
                const audioTrack = this.localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    this.isAudioEnabled = audioTrack.enabled;
                }
            }
        },

        async endCall() {
            // Stop tracks
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => track.stop());
                this.localStream = null;
            }

            if (this.remoteStream) {
                this.remoteStream.getTracks().forEach(track => track.stop());
                this.remoteStream = null;
            }

            // Close peer connection
            if (this.peerConnection) {
                this.peerConnection.close();
                this.peerConnection = null;
            }

            // Update call log
            if (this.callLog && this.isCallActive) {
                try {
                    await axios.post(`/calls/${this.callLog.id}/end`, {
                        duration: this.callDuration
                    });
                } catch (error) {
                    console.error('Error ending call:', error);
                }
            }

            // Reset state
            this.isCallActive = false;
            this.callLog = null;
            this.stopCallDuration();
            
            // Clear video elements
            const localVideo = document.getElementById('local-video');
            const remoteVideo = document.getElementById('remote-video');
            if (localVideo) localVideo.srcObject = null;
            if (remoteVideo) remoteVideo.srcObject = null;
        },

        displayLocalStream() {
            const localVideo = document.getElementById('local-video');
            if (localVideo && this.localStream) {
                localVideo.srcObject = this.localStream;
            }
        },

        displayRemoteStream() {
            const remoteVideo = document.getElementById('remote-video');
            if (remoteVideo && this.remoteStream) {
                remoteVideo.srcObject = this.remoteStream;
            }
        },

        startCallDuration() {
            this.callStartTime = Date.now();
            this.durationInterval = setInterval(() => {
                this.callDuration = Math.floor((Date.now() - this.callStartTime) / 1000);
            }, 1000);
        },

        stopCallDuration() {
            if (this.durationInterval) {
                clearInterval(this.durationInterval);
                this.durationInterval = null;
            }
            this.callDuration = 0;
        },

        formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    }
}

