<?php 
// FILE: psychiatrist/meeting.php

if(!isset($_GET['id'])){
    die("Error: No appointment ID provided.");
}

$appt_id = htmlspecialchars($_GET['id']);

// Generate a Consistent Room Name
// removing special characters to ensure it works on all devices
$room_name = "SafeSpace_Consultation_" . preg_replace("/[^a-zA-Z0-9]/", "", $appt_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Room #<?php echo $appt_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { margin: 0; padding: 0; height: 100%; background: #202020; font-family: 'Segoe UI', sans-serif; overflow: hidden; }
        
        /* Header */
        .header {
            position: absolute; top: 0; left: 0; width: 100%; height: 60px;
            background: #1a1a1a; border-bottom: 1px solid #333;
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 20px; box-sizing: border-box; z-index: 10;
        }
        .title { color: white; font-size: 18px; font-weight: 600; }
        
        .btn-close {
            background: #ef4444; color: white; padding: 8px 16px; 
            border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px;
        }
        .btn-close:hover { background: #dc2626; }

        /* Video Container */
        #meet { width: 100%; height: 100%; padding-top: 60px; box-sizing: border-box; background: #000; }
        
        /* Admin Instruction Overlay */
        #admin-hint {
            position: absolute; top: 80px; left: 50%; transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 8px;
            text-align: center; z-index: 20; box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            max-width: 90%;
        }
        .hide { display: none; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title"><i class="fa-solid fa-video"></i> SafeSpace Live</div>
        <a href="javascript:window.close()" class="btn-close">End Call</a>
    </div>

    <div id="admin-hint">
        <strong><i class="fa-solid fa-circle-info"></i> For the Doctor/Admin:</strong><br>
        If you see "Waiting for moderator", click the blue <b>"Log-in"</b> button on screen.<br>
        <small>Sign in with Google/GitHub to start the meeting for the patient.</small>
        <br><br>
        <button onclick="document.getElementById('admin-hint').classList.add('hide')" style="padding:5px 10px; cursor:pointer;">OK, I understand</button>
    </div>

    <div id="meet"></div>

    <script src='https://meet.jit.si/external_api.js'></script>
    <script>
        window.onload = function() {
            const domain = 'meet.jit.si';
            const options = {
                roomName: '<?php echo $room_name; ?>',
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#meet'),
                configOverwrite: { 
                    startWithAudioMuted: false, 
                    startWithVideoMuted: false,
                    prejoinPageEnabled: false 
                },
                interfaceConfigOverwrite: { 
                    SHOW_JITSI_WATERMARK: false,
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                        'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                        'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                        'videoquality', 'filmstrip', 'feedback', 'stats', 'shortcuts',
                        'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone'
                    ]
                },
                userInfo: {
                    displayName: "Participant" 
                }
            };
            const api = new JitsiMeetExternalAPI(domain, options);
            
            // Auto-hide the hint when user joins
            api.addEventListener('videoConferenceJoined', () => {
                document.getElementById('admin-hint').classList.add('hide');
            });
            
            // Close window on hangup
            api.addEventListener('videoConferenceLeft', () => {
                window.close();
            });
        };
    </script>
</body>
</html>