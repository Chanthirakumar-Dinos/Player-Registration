<?php
include 'db_config.php';

$player_id = ""; $phone = ""; $name = ""; $role = ""; $style = ""; $club = ""; $target_file = "";
$search_done = false;
$error_msg = "";
$success_msg = "";

// STEP 1: Search Player by Phone Number
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_player'])) {
    $phone = trim($_POST['phone']);
    
    if (empty($phone)) {
        $error_msg = "Please enter a phone number to search!";
    } else {
        $stmt = $conn->prepare("SELECT player_id, name, role, style, club_name, photo_path FROM players WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $player = $res->fetch_assoc();
            $player_id   = $player['player_id'];
            $name        = $player['name'];
            $role        = $player['role'];
            $style       = $player['style'];
            $club        = $player['club_name'];
            $target_file = $player['photo_path'];
            $search_done = true;
        } else {
            $error_msg = "No player found with this phone number!";
        }
        $stmt->close();
    }
}

// STEP 2: Update Player Details (Including Base64 Cropped Photo String)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_player'])) {
    $player_id = $_POST['player_id']; 
    $new_phone = trim($_POST['player_phone']); 
    $name      = trim($_POST['player_name']);
    $role      = $_POST['player_role'];
    $style     = $_POST['player_style'];
    $club      = trim($_POST['club_name']);
    $existing_photo = $_POST['existing_photo'];
    
    $target_file = $existing_photo; 

    // Duplicate Phone Number validation matrix
    $dup_check = $conn->prepare("SELECT player_id FROM players WHERE phone = ? AND player_id != ?");
    $dup_check->bind_param("ss", $new_phone, $player_id);
    $dup_check->execute();
    $dup_res = $dup_check->get_result();

    if ($dup_res->num_rows > 0) {
        $error_msg = "🚨 This new phone number is already registered by another player!";
        $search_done = true; 
    } else {
        // 🔴 Decode and Save Cropped Base64 Photo data if uploaded
        if (isset($_POST['cropped_image_base64']) && !empty($_POST['cropped_image_base64'])) {
            $base64_string = $_POST['cropped_image_base64'];
            $data = base64_decode(str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $base64_string));
            
            $new_file = "uploads/" . time() . "_updated_player.jpg";
            if (!file_exists("uploads/")) {
                mkdir("uploads/", 0777, true);
            }
            
            if (file_put_contents($new_file, $data)) {
                $target_file = $new_file;
                // Delete the old photo file from server stack cleanly
                if (file_exists($existing_photo) && !empty($existing_photo)) {
                    @unlink($existing_photo);
                }
            }
        }

        // DB Update Stream Logic
        $update_stmt = $conn->prepare("UPDATE players SET phone = ?, name = ?, role = ?, style = ?, club_name = ?, photo_path = ? WHERE player_id = ?");
        $update_stmt->bind_param("sssssss", $new_phone, $name, $role, $style, $club, $target_file, $player_id);
        
        if ($update_stmt->execute()) {
            echo "<form id='redirectForm' action='profile.php' method='POST'>
                    <input type='hidden' name='player_name' value='".htmlspecialchars($name)."'>
                    <input type='hidden' name='player_role' value='".htmlspecialchars($role)."'>
                    <input type='hidden' name='player_style' value='".htmlspecialchars($style)."'>
                    <input type='hidden' name='club_name' value='".htmlspecialchars($club)."'>
                    <input type='hidden' name='phone' value='".htmlspecialchars($new_phone)."'>
                  </form>
                  <script>document.getElementById('redirectForm').submit();</script>";
            exit;
        } else {
            $error_msg = "Something went wrong! Unable to update database.";
            $search_done = true;
        }
        $update_stmt->close();
    }
    $dup_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        body {
            background: #0b0c10;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .form-container {
            background: #1f2833;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 2px solid rgba(231, 76, 60, 0.2);
            box-sizing: border-box;
        }

        .logo-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
        }
        .top-club-logo {
            width: 85px;
            height: 85px;
            background: #fff;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            object-fit: contain;
            border: 2px solid #e74c3c;
        }

        h2 {
            text-align: center;
            font-weight: 900;
            margin-top: 0;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #e74c3c;
            text-shadow: 0 0 10px rgba(231, 76, 60, 0.3);
        }

        .input-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 700;
            font-size: 13px;
            color: #c5a880;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"], input[type="tel"], select, input[type="file"] {
            background: #0f141c;
            border: 2px solid #2c3540;
            padding: 12px;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            border-radius: 6px;
            outline: none;
            transition: 0.2s ease;
            box-sizing: border-box;
            width: 100%;
        }

        input:focus, select:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 10px rgba(231, 76, 60, 0.2);
        }

        .btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 14px;
            font-weight: 900;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.2s;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.5);
        }

        .btn-search {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            border: 2px solid #34495e;
            box-shadow: none;
        }

        .msg {
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .msg-error { background: #c0392b; color: #fff; }

        .current-photo-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #0f141c;
            padding: 10px;
            border-radius: 6px;
            border: 2px solid #2c3540;
            margin-top: 10px;
        }

        .current-photo-preview img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e74c3c;
        }

        .current-photo-preview span {
            font-size: 12px;
            color: #888;
        }

        /* 🔴 CROPPER MODAL STYLES */
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
            background: #1f2833;
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
            background: #e74c3c;
            color: #fff;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .preview-box {
            display: none;
            margin-top: 12px;
            text-align: center;
        }
        .preview-box img {
            width: 70px;
            height: 70px;
            border-radius: 6px;
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

    <h2>Edit Player Profile</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="msg msg-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form action="edit_profile.php" method="POST" style="<?php echo $search_done ? 'display:none;' : ''; ?>">
        <div class="input-group">
            <label for="phone">Enter Registered Phone Number</label>
            <input type="tel" id="phone" name="phone" placeholder="e.g. 0775063549" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>
        <button type="submit" name="search_player" class="btn btn-search">Find My Profile</button>
    </form>

    <?php if ($search_done): ?>
        <form id="editForm" action="edit_profile.php" method="POST">
            
            <input type="hidden" name="player_id" value="<?php echo htmlspecialchars($player_id); ?>">
            <input type="hidden" name="existing_photo" value="<?php echo htmlspecialchars($target_file); ?>">

            <div class="input-group">
                <label for="player_phone">Phone Number (Editable)</label>
                <input type="text" id="player_phone" name="player_phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>

            <div class="input-group">
                <label for="player_name">Player Name</label>
                <input type="text" id="player_name" name="player_name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="input-group">
                <label for="player_role">Role</label>
                <select id="player_role" name="player_role" required>
                    <option value="ALL ROUNDER" <?php if($role == "ALL ROUNDER" || $role == "All Rounder") echo "selected"; ?>>ALL ROUNDER</option>
                    <option value="BATSMAN" <?php if($role == "BATSMAN" || $role == "Batsman") echo "selected"; ?>>BATSMAN</option>
                    <option value="BOWLER" <?php if($role == "BOWLER" || $role == "Bowler") echo "selected"; ?>>BOWLER</option>
                    <option value="WICKET KEEPER" <?php if($role == "WICKET KEEPER") echo "selected"; ?>>WICKET KEEPER</option>
                </select>
            </div>

            <div class="input-group">
                <label for="player_style">Style</label>
                <select id="player_style" name="player_style" required>
                    <option value="RIGHTHAND" <?php if($style == "RIGHTHAND" || $style == "Righthand") echo "selected"; ?>>RIGHTHAND</option>
                    <option value="LEFTHAND" <?php if($style == "LEFTHAND" || $style == "Lefthand") echo "selected"; ?>>LEFTHAND</option>
                </select>
            </div>

            <div class="input-group">
                <label for="club_name">Club Name</label>
                <input type="text" id="club_name" name="club_name" value="<?php echo htmlspecialchars($club); ?>" required>
            </div>

            <div class="input-group">
                <label>Change Profile Photo (நீங்கள் விரும்பியவாறு Crop செய்து கொள்ளலாம்):</label>
                <input type="file" id="player_photo_input" accept="image/*">
                
                <input type="hidden" id="cropped_image_data" name="cropped_image_base64">

                <div id="previewContainer" class="preview-box">
                    <p style="font-size: 11px; color: #45e94dff; margin: 5px 0;">✔ புதிய புகைப்படம் ரெடி:</p>
                    <img id="croppedPreview" src="" alt="New Preview">
                </div>

                <?php if (!empty($target_file) && file_exists($target_file)): ?>
                    <div class="current-photo-preview" id="currentPhotoContainer">
                        <img src="<?php echo $target_file; ?>" alt="Current Photo">
                        <div>
                            <p style="margin:0; font-size:13px; font-weight:bold;">Current Photo</p>
                            <span>Leave empty if you don't want to change it</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" name="update_player" class="btn">Update & View Poster</button>
        </form>
    <?php endif; ?>
</div>

<div id="cropperModal" class="cropper-modal-container">
    <h3 style="color: #fff; margin-bottom: 10px; font-size: 15px; text-transform: uppercase;">புகைப்படத்தை 1:1 சதுரமாக அட்ஜஸ்ட் செய்யவும்</h3>
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
const currentPhotoContainer = document.getElementById('currentPhotoContainer');

if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imageToCrop.src = event.target.result;
                cropperModal.style.display = 'flex'; 

                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1, 
                    viewMode: 1,
                    background: false,
                    autoCropArea: 1
                });
            };
            reader.readAsDataURL(file);
        }
    });
}

if (cropActionBtn) {
    cropActionBtn.addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 600, 
                height: 600
            });

            const base64Image = canvas.toDataURL('image/jpeg', 0.95);
            croppedImageData.value = base64Image;
            
            croppedPreview.src = base64Image;
            previewContainer.style.display = 'block';
            if(currentPhotoContainer) currentPhotoContainer.style.opacity = "0.4"; // Fade old photo preview
            cropperModal.style.display = 'none'; 
        }
    });
}

if (cancelCropBtn) {
    cancelCropBtn.addEventListener('click', function() {
        cropperModal.style.display = 'none';
        fileInput.value = ""; 
    });
}
</script>
</body>
</html>