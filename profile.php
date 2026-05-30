<?php
include 'db_config.php';

$player_id = ""; $name = ""; $role = ""; $style = ""; $club = ""; $phone = ""; $target_file = "";
$logo_file = "logo.png"; // Default Club Logo Image Path (index.php la irukura adhe logo.png)
$company_logo = "company_logo1.png"; // Left Side Bottom Company Logo Path
$is_duplicate = false;       

// STEP 1: Handle Data coming from Registration Form (index.php)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['player_name'])) {
    $phone = trim($_POST['phone']);

    // Check if phone number already exists
    $check_stmt = $conn->prepare("SELECT player_id, name, role, style, club_name, photo_path FROM players WHERE phone = ?");
    $check_stmt->bind_param("s", $phone);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        // Player already exists - Fetch old data to show poster
        $is_duplicate = true; 
        $existing_data = $check_res->fetch_assoc();
        $player_id   = $existing_data['player_id'];
        $name        = $existing_data['name'];
        $role        = $existing_data['role'];
        $style       = $existing_data['style'];
        $club        = $existing_data['club_name'];
        $target_file = $existing_data['photo_path'];
    } else {
        // New Registration Process
        $name  = $_POST['player_name'];
        $role  = $_POST['player_role'];
        $style = $_POST['player_style'];
        $club  = $_POST['club_name'];

        // Generate Custom Player ID
        $res = $conn->query("SELECT id FROM players");
        $player_id = "PLAYER-" . str_pad($res->num_rows + 1, 3, '0', STR_PAD_LEFT);

        // 🔴 Handle Cropped Base64 Image String from Cropper.js
        if (isset($_POST['cropped_image_base64']) && !empty($_POST['cropped_image_base64'])) {
            $base64_string = $_POST['cropped_image_base64'];
            // Cleaning up the base64 prefix
            $data = base64_decode(str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $base64_string));
            
            // Generate unique filename and save to uploads folder
            $target_file = "uploads/" . time() . "_player.jpg";
            if (!file_exists("uploads/")) {
                mkdir("uploads/", 0777, true);
            }
            file_put_contents($target_file, $data);
        }

        // Insert into Database
        $stmt = $conn->prepare("INSERT INTO players (player_id, name, role, style, club_name, phone, photo_path) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $player_id, $name, $role, $style, $club, $phone, $target_file);
        $stmt->execute();
        $stmt->close();
    }
    $check_stmt->close();
} 
// STEP 2: Handle Ajax request when Download Poster triggers (To save poster on server if needed)
else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['image_data'])) {
    $data = base64_decode(str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $_POST['image_data']));
    $folder = "saved_posters/";
    if (!file_exists($folder)) mkdir($folder, 0777, true);
    
    if (file_put_contents($folder . $_POST['filename'], $data)) echo "Success";
    else echo "Error saving file";
    exit;
} else {
    // If accessed directly without POST data, redirect back
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: index.php"); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Premium Player Stats</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,800;0,900;1,900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { background: #0b0c10; display: flex; flex-direction: column; align-items: center; padding: 40px; font-family: 'Montserrat', sans-serif; margin:0; }

        .alert-box {
            width: 700px;
            background: #e74c3c;
            color: #fff;
            padding: 14px;
            font-weight: 900;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .poster-main {
            width: 700px;  
            height: 500px; 
            background-image: url('poster_bg.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center; 
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
            border: 6px solid #1f2833;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }

        .left-photo-pane {
            width: 340px; 
            height: 340px;
            margin: 25px; 
            box-sizing: border-box;
            overflow: hidden;
            border-radius: 12px;
            background: #151515; 
            flex-shrink: 0; 
            border: 4px solid #e74c3c; 
            box-shadow: 0 0 20px rgba(231, 76, 60, 0.6); 
        }
        
        .left-photo-pane img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
            object-position: center;
            display: block;
        }

        .right-info-pane {
            width: 310px; 
            height: 500px;
            background: linear-gradient(to right, rgba(26,26,36,0.3) 0%, rgba(15,15,22,0.85) 100%);
            padding: 40px 15px 40px 25px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            position: relative;
            margin-left: auto;
        }

        .club-logo-badge {
            position: absolute;
            top: 32px;
            right: 15px;
            width: 65px;
            height: 65px;
            background: #fff;
            border-radius: 50%;
            padding: 3px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        .club-logo-badge img {
            width: 92%;
            height: 92%;
            object-fit: contain;
        }

        /* 🔴 COMPANY LOGO STRIP CSS RULES */
        .company-logo-badge {
            position: absolute;
            bottom: 25px; /* Bottom edge lock */
            left: 25px;   /* Left edge alignment lock */
            height: 40px; /* Premium Sinna Size attribute */
            max-width: 150px;
            display: flex;
            align-items: center;
            z-index: 10;
        }
        .company-logo-badge img {
            height: 100%;
            width: auto;
            object-fit: contain;
            margin-left: 173px;
            margin-bottom: -43px;
            filter: drop-shadow(0px 2px 8px rgba(0,0,0,0.5)); /* Blends cleanly with any background */
        }

        .name-ribbon {
            background: #000000;
            padding: 12px 15px;
            transform: skewX(-12deg);
            margin-bottom: 35px;
            width: 68%; 
            border-left: 5px solid #e74c3c;
            box-shadow: -5px 5px 15px rgba(0,0,0,0.3);
            margin-left: -13px;
        }
        .name-ribbon h1 {
            color: #ffffff;
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            font-style: italic;
            text-align: left;
            transform: skewX(12deg);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .stat-field {
            display: flex;
            align-items: center;
            border-bottom: 1.5px solid rgba(255,255,255,0.12);
            padding: 10px 0;
            margin-bottom: 6px;
        }
        .field-title {
            width: 90px;
            font-size: 14px;
            font-weight: 900;
            color: #e74c3c; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field-data {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .system-id {
            position: absolute;
            bottom: 25px;
            left: 25px;
            font-size: 22px;
            font-weight: 800;
            color: rgba(255,255,255,0.7);
            letter-spacing: 1px;
        }

        /* 🔘 BUTTON CONTROL AREA */
        .button-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 14px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
            transition: 0.2s ease;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 25px rgba(231, 76, 60, 0.5);
        }

        /* Secondary Gaming Gray Button style for Edit */
        .edit-button {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            border: 2px solid #34495e;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .edit-button:hover {
            background: #34495e;
            box-shadow: 0 7px 20px rgba(52, 152, 219, 0.3);
        }

        h2 { 
            text-align: center;
            color: #45e94dff;
            letter-spacing: 1px;
            margin-top: 20px;
            font-size: 20px;
        }
    </style>
</head>
<body>

<?php if ($is_duplicate): ?>
    <div class="alert-box">
        🚨 Already Registered! You can directly download or edit your stats card below.
    </div>
<?php endif; ?>

<div class="poster-main" id="posterArea">
    <div class="left-photo-pane">
        <div class="system-id" style="font-size: 17px; margin-bottom: 405px; margin-left: 8px; color: red;">ParimalaKanthy Premier League</div>
        <img src="<?php echo $target_file; ?>" crossorigin="anonymous">
        <br>
        <div class="system-id" style="font-size: 15px;">INFINITY CREATIVE</div>
        <br>
        <div class="system-id" style="font-size: 10px; margin-bottom: -15px; color: red;">Contact Us : 0775063549</div>
        <div class="company-logo-badge">
            <img src="<?php echo $company_logo; ?>" crossorigin="anonymous" onerror="this.style.display='none';">
        </div>
    </div>

    <div class="right-info-pane">
        <div class="club-logo-badge">
            <img src="<?php echo $logo_file; ?>" crossorigin="anonymous">
        </div>

        <div class="name-ribbon">
            <h1><?php echo htmlspecialchars($name); ?></h1>
        </div>

        <div class="stat-field">
            <span class="field-title">ROLE</span>
            <span class="field-data"><?php echo htmlspecialchars($role); ?></span>
        </div>
        <div class="stat-field">
            <span class="field-title">STYLE</span>
            <span class="field-data"><?php echo htmlspecialchars($style); ?></span>
        </div>
        <div class="stat-field">
            <span class="field-title">CLUB</span>
            <span class="field-data"><?php echo htmlspecialchars($club); ?></span>
        </div>
        <div class="stat-field" style="border:none;">
            <span class="field-title">PHONE</span>
            <span class="field-data"><?php echo htmlspecialchars($phone); ?></span>
        </div>
        <br>
        <h2>PARIMALAKANTHI SPORTS CLUB - 2026</h2>
        
        <div class="system-id">ID: <?php echo $player_id; ?></div>
    </div>
</div>

<div class="button-container">
    <button class="action-button" onclick="exportPosterAsJPG()">DOWNLOAD POSTER</button>
    
    <form action="edit_profile.php" method="POST" style="margin:0;">
        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
        <button type="submit" name="search_player" class="action-button edit-button">EDIT PROFILE</button>
    </form>
</div>

<script>
function exportPosterAsJPG() {
    const targetDiv = document.getElementById('posterArea');
    const playerRawName = "<?php echo addslashes($name); ?>";
    const structuredFileName = playerRawName.replace(/\s+/g, '_') + '_Stats.jpg';

    html2canvas(targetDiv, { 
        useCORS: true, 
        allowTaint: false,
        scale: 3,               
        width: 700,             
        height: 500,            
        windowWidth: 700,       
        windowHeight: 500,      
        scrollX: 0,
        scrollY: 0,
        logging: false
    }).then(generatedCanvas => {
        const ctx = generatedCanvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        const streamData = generatedCanvas.toDataURL('image/jpeg', 0.98);
        
        const activeLink = document.createElement('a');
        activeLink.download = structuredFileName; 
        activeLink.href = streamData; 
        activeLink.click();

        let payload = new FormData();
        payload.append('image_data', streamData);
        payload.append('filename', structuredFileName);
        fetch('profile.php', { method: 'POST', body: payload });
    }).catch(err => {
        console.error("Canvas export failed: ", err);
    });
}
</script>
</body>
</html>