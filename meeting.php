<?php 
// FILE: psychiatrist/meeting.php

if(!isset($_GET['id'])){
    die("Error: No appointment ID provided.");
}

$appt_id = htmlspecialchars($_GET['id']);

// Generate a Consistent Room Name (Removes spaces/special chars)
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
        
        /* Fallback / External Link Overlay */
        #external-link-msg {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            text-align: center; z-index: 5; color: #aaa;
            display: none; /* Shows if video fails or takes too long */
        }
        
        .btn-external {
            display: inline-block; margin-top: 10px; padding: 12px 24px;
            background-color: #0ea5e9; color: white; text-decoration: none;
            border-radius: 8px; font-weight: bold; font-size: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .btn-external:hover { background-color: #0284c7; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title"><i class="fa-solid fa-video"></i> SafeSpace Live</div>
        <a href="javascript:window.close()" class="btn-close">End Call</a>
    </div>

    <div id="external-link-msg">
        <h2><i class="fa-solid fa-video-slash"></i> Video not loading?</h2>
        <p>If you see a "Waiting for moderator" screen or cannot log in:</p>
        <a href="https://meet.jit.si/<?php echo $room_name; ?>" target="_blank" class="btn-external">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Meeting in New Tab
        </a>
    </div>

    <div id="meet"></div>

    <script src='https://meet.jit.si/external_api.js'></script>
    <script>
        window.onload = function() {
            // Show fallback button after 3 seconds just in case
            setTimeout(() => {
                document.getElementById('external-link-msg').style.display = 'block';
            }, 3000);

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
                    SHOW_JITSI_WATERMARK: false 
                },
                userInfo: {
                    displayName: "Participant" 
                }
            };
            const api = new JitsiMeetExternalAPI(domain, options);
            
            // Hide fallback if join is successful
            api.addEventListener('videoConferenceJoined', () => {
                document.getElementById('external-link-msg').style.display = 'none';
            });
            
            api.addEventListener('videoConferenceLeft', () => {
                window.close();
            });
        };
    </script>
</body>
</html>