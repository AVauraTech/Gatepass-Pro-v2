<?php
// edit-pass-detail.php - Update Pass Details & Re-Compute HMAC-SHA256 Token
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$message = '';
$pass_id = intval($_GET['id'] ?? 0);

if (isset($_POST['submit'])) {
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $contactno = mysqli_real_escape_string($conn, trim($_POST['contactno']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $idtype = mysqli_real_escape_string($conn, trim($_POST['idtype']));
    $idcardno = mysqli_real_escape_string($conn, trim($_POST['idcardno']));
    $vehicle_no = mysqli_real_escape_string($conn, trim($_POST['vehicle_no'] ?: 'N/A'));
    $security_clearance = mysqli_real_escape_string($conn, trim($_POST['security_clearance'] ?: 'Standard'));
    $host_name = mysqli_real_escape_string($conn, trim($_POST['host_name'] ?: 'Security Operations'));
    $host_department = mysqli_real_escape_string($conn, trim($_POST['host_department'] ?: 'Operations'));
    $fromdate = mysqli_real_escape_string($conn, trim($_POST['fromdate']));
    $todate = mysqli_real_escape_string($conn, trim($_POST['todate']));
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status'] ?: 'Approved'));

    // Fetch pass number to re-compute HMAC
    $currQuery = mysqli_query($conn, "SELECT pass_number FROM passes WHERE id='$pass_id' LIMIT 1");
    $currRow = mysqli_fetch_assoc($currQuery);
    $passNumber = $currRow ? $currRow['pass_number'] : mt_rand(100000000, 999999999);

    $newHmac = generatePassHMAC($passNumber, $idcardno, $todate, $category);

    $sql = "UPDATE passes SET 
            category='$category', 
            full_name='$fullname', 
            contact_number='$contactno', 
            email='$email', 
            identity_type='$idtype', 
            identity_card_no='$idcardno', 
            vehicle_no='$vehicle_no',
            security_clearance='$security_clearance',
            host_name='$host_name',
            host_department='$host_department',
            from_date='$fromdate', 
            to_date='$todate', 
            reason='$reason',
            status='$status',
            hmac_hash='$newHmac'
            WHERE id='$pass_id'";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        logCheckpointEvent($conn, $passNumber, $fullname, 'PASS_MODIFIED', 'Admin Terminal', 'ADMIN-' . $_SESSION['admin_id'], null, $vehicle_no, "Pass details updated and HMAC re-signed");
        $message = "<div class='alert-toast success'><i class='fas fa-check-circle'></i> Pass #$passNumber updated successfully and cryptographic signature re-verified!</div>";
    } else {
        $message = "<div class='alert-toast error'><i class='fas fa-exclamation-circle'></i> Error updating pass: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    }
}

$ret = mysqli_query($conn, "SELECT * FROM passes WHERE id='$pass_id' LIMIT 1");
$row = mysqli_fetch_assoc($ret);
if (!$row) {
    header("location:manage-passes.php");
    exit;
}

$active_page = 'manage-passes.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
        .edit-card {
            background: rgba(18, 28, 38, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.28);
            border-radius: 24px;
            padding: 34px 38px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
            max-width: 820px;
            margin: 0 auto 30px auto;
        }
        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 255, 195, 0.2);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .edit-header h2 {
            margin: 0;
            color: #24ffe3;
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        label {
            font-size: 0.74rem;
            font-weight: 700;
            color: #799ea8;
            margin-bottom: 6px;
            letter-spacing: 0.04em;
        }
        input, select, textarea {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.25);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #00ffc3;
            box-shadow: 0 0 14px rgba(0, 255, 195, 0.35);
            background: rgba(255,255,255,0.1);
        }
        select option {
            background: #102430;
            color: #fff;
        }
        .btn-update {
            grid-column: span 2;
            background: linear-gradient(135deg, #0099ff, #0066cc);
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 153, 255, 0.4);
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 153, 255, 0.6);
        }
        .alert-toast {
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .alert-toast.success {
            background: rgba(0, 255, 195, 0.15);
            border: 1px solid #00ffc3;
            color: #00ffc3;
        }
        .alert-toast.error {
            background: rgba(255, 82, 82, 0.15);
            border: 1px solid #ff5252;
            color: #ff5252;
        }
    </style>

<?php echo $message; ?>

    <div class="edit-card">
        <div class="edit-header">
            <h2><i class="fas fa-edit"></i> EDIT PASS #<?php echo htmlspecialchars($row['pass_number']); ?></h2>
            <a href="view-pass-detail.php?id=<?php echo $pass_id; ?>" style="color:#00ffc3; text-decoration:none; font-size:0.85rem;"><i class="fas fa-eye"></i> View Dossier</a>
        </div>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>VISITOR FULL NAME *</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>ACCESS CATEGORY *</label>
                    <select name="category" required>
                        <?php 
                        $cats = ["Visitor", "Contractor", "VIP Delegate", "Maintenance", "Security Auditor", "Research Scientist", "Candidate"];
                        foreach ($cats as $c) {
                            $sel = ($row['category'] === $c) ? 'selected' : '';
                            echo "<option value='$c' $sel>$c</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>CONTACT PHONE *</label>
                    <input type="tel" name="contactno" value="<?php echo htmlspecialchars($row['contact_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>EMAIL ADDRESS *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>IDENTITY CARD TYPE *</label>
                    <select name="idtype" required>
                        <?php 
                        $types = ["Aadhaar Card", "Adhar Card", "Passport", "Driving License", "Voter ID", "Corporate Badge"];
                        foreach ($types as $t) {
                            $sel = ($row['identity_type'] === $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>IDENTITY CARD NUMBER *</label>
                    <input type="text" name="idcardno" value="<?php echo htmlspecialchars($row['identity_card_no']); ?>" required>
                </div>
                <div class="form-group">
                    <label>VEHICLE REGISTRATION (ALPR)</label>
                    <input type="text" name="vehicle_no" value="<?php echo htmlspecialchars($row['vehicle_no'] ?: 'N/A'); ?>">
                </div>
                <div class="form-group">
                    <label>SECURITY CLEARANCE TIER</label>
                    <select name="security_clearance">
                        <?php 
                        $tiers = ["Standard", "Confidential", "Secret", "VIP Delegate"];
                        foreach ($tiers as $tier) {
                            $sel = (($row['security_clearance'] ?? 'Standard') === $tier) ? 'selected' : '';
                            echo "<option value='$tier' $sel>$tier</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>INTERNAL HOST CONTACT</label>
                    <input type="text" name="host_name" value="<?php echo htmlspecialchars($row['host_name'] ?? 'Security Operations'); ?>">
                </div>
                <div class="form-group">
                    <label>HOST DEPARTMENT</label>
                    <input type="text" name="host_department" value="<?php echo htmlspecialchars($row['host_department'] ?? 'General Operations'); ?>">
                </div>
                <div class="form-group">
                    <label>FROM VALIDITY DATE *</label>
                    <input type="date" name="fromdate" value="<?php echo htmlspecialchars($row['from_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label>TO EXPIRATION DATE *</label>
                    <input type="date" name="todate" value="<?php echo htmlspecialchars($row['to_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label>CURRENT PERMIT STATUS</label>
                    <select name="status">
                        <?php 
                        $statuses = ["Approved", "Checked-In", "Checked-Out", "Pending", "Flagged"];
                        foreach ($statuses as $st) {
                            $sel = ($row['status'] === $st) ? 'selected' : '';
                            echo "<option value='$st' $sel>$st</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>PURPOSE OF VISIT / REASON *</label>
                    <textarea name="reason" rows="2" required><?php echo htmlspecialchars($row['reason']); ?></textarea>
                </div>
                <button type="submit" name="submit" class="btn-update">
                    <i class="fas fa-save"></i> UPDATE CREDENTIAL & RE-SIGN HMAC HASH
                </button>
            </div>
        </form>
    </div>

<?php include 'includes/footer.php'; ?>
