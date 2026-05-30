<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #1a1a2e; 
            color: #fff; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
            box-sizing: border-box;
        }
        .form-container { 
            background-image: url('index_bg.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
            width: 100%; 
            max-width: 400px; 
            border: 1px solid #0f3460; 
            box-sizing: border-box;
            position: relative;
        }

        .logo-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
        }
        .top-club-logo {
            width: 85px;
            height: 85px;
            background: #fff;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 4px 15px rgba(69, 233, 77, 0.3);
            object-fit: contain;
            border: 2px solid #45e94dff;
        }

        h1 { 
            text-align: center; 
            color: #45e94dff; 
            letter-spacing: 1px;
            margin: 5px 0;
            font-size: 1.6rem;
        }
        h2 { 
            text-align: center; 
            color: #45e94dff;
            letter-spacing: 1px;
            margin: 5px 0 15px 0;
            font-size: 1.2rem;
        }
        h3 { 
            text-align: center; 
            color: #e94560; 
            letter-spacing: 1px;
            margin: 15px 0 20px 0;
            font-size: 1.1rem;
            font-weight: bold;
        }

        label { 
            display: block; 
            margin-top: 15px; 
            font-size: 0.9rem; 
            color: #ccc; 
        }
        input, select { 
            width: 100%; 
            padding: 12px; 
            margin-top: 10px; 
            border-radius: 8px; 
            border: none; 
            background: #0f3460; 
            color: #fff; 
            box-sizing: border-box; 
            font-size: 14px; 
        }
        input:focus, select:focus { 
            outline: 2px solid #e94560; 
        }
        button { 
            width: 100%; 
            padding: 12px; 
            margin-top: 25px; 
            border: none; 
            border-radius: 8px; 
            background: #e94560; 
            color: white; 
            font-weight: bold; 
            cursor: pointer; 
            transition: 0.3s; 
            font-size: 16px; 
        }
        button:hover { 
            background: #ff2e63; 
        }

        /* 🔴 CROPPER MODAL POPUP STYLES */
        .cropper-modal-container {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
        }
        .cropper-wrapper {
            max-width: 90%;
            max-height: 70vh;
            background: #16213e;
            padding: 10px;
            border-radius: 8px;
        }
        .cropper-wrapper img {
            max-width: 100%;
            display: block;
        }
        .cropper-btns {
            margin-top: 15px;
            display: flex;
            gap: 15px;
        }
        .crop-btn {
            background: #45e94dff;
            color: #000;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .cancel-btn {
            background: #e94560;
            color: #fff;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Preview Thumbnail */
        .preview-box {
            display: none;
            margin-top: 15px;
            text-align: center;
        }
        .preview-box img {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            border: 2px solid #45e94dff;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="logo-wrapper">
            <img src="logo.png" alt="Club Logo" class="top-club-logo" onerror="this.style.display='none';">
        </div>

        <h1>PARIMALAKANTHY</h1>
        <h2>PREMIER LEAGUE</h2>
        <h3>PLAYER REGISTRATION</h3>
        
        <form id="registrationForm" action="profile.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="player_name" placeholder="Full Name" required>
            <input type="text" name="club_name" placeholder="Club Name" required>
            
            <select name="player_role" required>                
                <option value="Batsman">Batsman</option>
                <option value="Bowler">Bowler</option>
                <option value="All Rounder">All Rounder</option>
            </select>
            
            <select name="player_style" required>
                <option value="Righthand">Right Hand</option>
                <option value="Lefthand">Left Hand</option>
            </select>
            
            <input type="text" name="phone" placeholder="Phone Number" required>
            
            <label>Player Photo (எந்த சைஸ் போட்டோவையும் நீங்கள் 1:1 ஆக Crop செய்து கொள்ளலாம்):</label>
            <input type="file" id="player_photo_input" accept="image/*" required>
            
            <input type="hidden" id="cropped_image_data" name="cropped_image_base64">

            <div id="previewContainer" class="preview-box">
                <p style="font-size: 12px; color: #45e94dff; margin: 5px 0;">✔ தயாரான புகைப்படம்:</p>
                <img id="croppedPreview" src="" alt="Preview">
            </div>

            <button type="submit">GENERATE PROFILE</button>
        </form>
    </div>

    <div id="cropperModal" class="cropper-modal-container">
        <h3 style="color: #fff; margin-bottom: 10px;">உங்கள் புகைப்படத்தை சதுரமாக (1:1) அட்ஜஸ்ட் செய்யவும்</h3>
        <div class="cropper-wrapper">
            <img id="imageToCrop" src="" alt="Source Image">
        </div>
        <div class="cropper-btns">
            <button type="button" class="crop-btn" id="cropActionBtn">CROP PHOTO</button>
            <button type="button" class="cancel-btn" id="cancelCropBtn">CANCEL</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
    let cropper;
    const fileInput = document.getElementById('player_photo_input');
    const cropperModal = document.getElementById('cropperModal');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropActionBtn = document.getElementById('cropActionBtn');
    const cancelCropBtn = document.getElementById('cancelCropBtn');
    const croppedImageData = document.getElementById('cropped_image_data');
    const previewContainer = document.getElementById('previewContainer');
    const croppedPreview = document.getElementById('croppedPreview');

    // 1. User photo-vah select பண்ணும்போது நடக்கும் செயல்
    fileInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                cropperModal.style.display = 'flex'; // Pop up modal show பண்ணும்

                // Cropper-ஐ 1:1 ratio-வில் லாக் செய்கிறோம்
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1, // Strict 1:1 aspect ratio square target lock
                    viewMode: 1,
                    background: false,
                    autoCropArea: 1
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // 2. Crop பட்டன் கிளிக் செய்யும்போது
    cropActionBtn.addEventListener('click', function() {
        if (cropper) {
            // High quality 1:1 image canvas-ஐ எடுக்கிறது
            const canvas = cropper.getCroppedCanvas({
                width: 600,  // Standard HD Square matrix size
                height: 600
            });

            // Base64 string ஆக மாற்றி hidden input மற்றும் preview-வில் சேர்க்கிறது
            const base64Image = canvas.toDataURL('image/jpeg', 0.95);
            croppedImageData.value = base64Image;
            
            croppedPreview.src = base64Image;
            previewContainer.style.display = 'block';
            cropperModal.style.display = 'none'; // Close window overlay
        }
    });

    // 3. Cancel செய்யும்போது
    cancelCropBtn.addEventListener('click', function() {
        cropperModal.style.display = 'none';
        fileInput.value = ""; // Clear selected data
    });
    </script>
</body>
</html>