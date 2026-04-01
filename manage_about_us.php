<?php
include 'header.php';

// Only admins can manage about us content
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Access Denied. Admins only.</div>";
    include 'footer.php';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_about'])) {
        $description = trim($_POST['description']);
        $vision = trim($_POST['vision']);
        $mission = trim($_POST['mission']);
        $contact_email = trim($_POST['contact_email']);
        $contact_phone = trim($_POST['contact_phone']);
        $address = trim($_POST['address']);
        
        $agency_image = $about_us['agency_service_image'];
        // Handle Agency Image upload (including cropped images)
        if (isset($_FILES['agency_image']) && $_FILES['agency_image']['error'] == 0) {
            $target_dir = "uploads/site/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Handle cropped image (special filename from cropper)
            if ($_FILES['agency_image']['name'] === 'cropped_image.jpg') {
                // Handle base64 cropped image - convert from temp file
                $temp_file = $_FILES['agency_image']['tmp_name'];
                $image_data = file_get_contents($temp_file);
                
                if ($image_data !== false) {
                    $target_file = $target_dir . 'agency_service_' . time() . '_cropped.jpg';
                    if (file_put_contents($target_file, $image_data)) {
                        $agency_image = $target_file;
                    }
                }
            } else {
                // Handle regular file upload
                $target_file = $target_dir . 'agency_service_' . time() . '_' . basename($_FILES['agency_image']['name']);
                if (move_uploaded_file($_FILES['agency_image']['tmp_name'], $target_file)) {
                    $agency_image = $target_file;
                }
            }
        }

        $office_image = $about_us['office_image'];
        // Handle Office Image upload (including cropped images)
        if (isset($_FILES['office_image']) && $_FILES['office_image']['error'] == 0) {
            $target_dir = "uploads/site/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Handle cropped image (special filename from cropper)
            if ($_FILES['office_image']['name'] === 'cropped_image.jpg') {
                // Handle base64 cropped image - convert from temp file
                $temp_file = $_FILES['office_image']['tmp_name'];
                $image_data = file_get_contents($temp_file);
                
                if ($image_data !== false) {
                    $target_file = $target_dir . 'office_' . time() . '_cropped.jpg';
                    if (file_put_contents($target_file, $image_data)) {
                        $office_image = $target_file;
                    }
                }
            } else {
                // Handle regular file upload
                $target_file = $target_dir . 'office_' . time() . '_' . basename($_FILES['office_image']['name']);
                if (move_uploaded_file($_FILES['office_image']['tmp_name'], $target_file)) {
                    $office_image = $target_file;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE about_us SET description = ?, vision = ?, mission = ?, contact_email = ?, contact_phone = ?, address = ?, agency_service_image = ?, office_image = ? WHERE id = 1");
        $stmt->bind_param("ssssssss", $description, $vision, $mission, $contact_email, $contact_phone, $address, $agency_image, $office_image);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>About Us content updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating content: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['add_official'])) {
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $bio = trim($_POST['bio']);
        $image_path = '';
        
        // Handle file upload (including cropped images)
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/officials/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Handle cropped image (special filename from cropper)
            if ($_FILES['image']['name'] === 'cropped_image.jpg') {
                // Handle base64 cropped image - convert from temp file
                $temp_file = $_FILES['image']['tmp_name'];
                $image_data = file_get_contents($temp_file);
                
                if ($image_data !== false) {
                    $target_file = $target_dir . time() . '_cropped.jpg';
                    if (file_put_contents($target_file, $image_data)) {
                        $image_path = $target_file;
                    }
                }
            } else {
                // Handle regular file upload
                $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO official_profiles (name, position, bio, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $position, $bio, $image_path);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Official profile added successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding official: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['update_official'])) {
        $id = (int)$_POST['official_id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $bio = trim($_POST['bio']);
        
        // Get existing image path
        $check = $conn->prepare("SELECT image_path FROM official_profiles WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $image_path = $existing['image_path'];
        $check->close();
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/officials/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image if it exists and is different
                if (!empty($image_path) && file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = $target_file;
            }
        }
        
        $stmt = $conn->prepare("UPDATE official_profiles SET name = ?, position = ?, bio = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $position, $bio, $image_path, $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Official profile updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating official: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_official'])) {
        $id = (int)$_POST['official_id'];
        $stmt = $conn->prepare("DELETE FROM official_profiles WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Official profile deleted successfully.</div>";
        }
        $stmt->close();
    }
}

// Fetch content
$about_us = $conn->query("SELECT * FROM about_us WHERE id = 1")->fetch_assoc();
$officials = $conn->query("SELECT * FROM official_profiles ORDER BY id ASC");

// Handle Edit Mode for Official
$edit_official = null;
if (isset($_GET['edit_official_id'])) {
    $edit_id = (int)$_GET['edit_official_id'];
    $edit_stmt = $conn->prepare("SELECT * FROM official_profiles WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_official = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}
?>

<div class="header">
    <h1 class="fw-bold">Manage <span>About Us</span></h1>
</div>

<?php echo $message; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card form-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Company Details</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Agency Service Image (Home Page)</label>
                        <?php if(!empty($about_us['agency_service_image'])): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="<?php echo htmlspecialchars($about_us['agency_service_image']); ?>" alt="Agency Image" style="width: 100px; height: auto; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="agency_image" class="form-control" accept="image/*" onchange="openCropper(this, 'agency')">
                        <small class="text-muted">Click to select image, then crop to fit perfectly</small>
                    </div>
                    <div class="form-group">
                        <label>Office/About Image (About Page)</label>
                        <?php if(!empty($about_us['office_image'])): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="<?php echo htmlspecialchars($about_us['office_image']); ?>" alt="Office Image" style="width: 100px; height: auto; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="office_image" class="form-control" accept="image/*" onchange="openCropper(this, 'office')">
                        <small class="text-muted">Click to select image, then crop to fit perfectly</small>
                    </div>
                    <div class="form-group">
                        <label>Company Description</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($about_us['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Vision</label>
                        <textarea name="vision" class="form-control" rows="2"><?php echo htmlspecialchars($about_us['vision'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Mission</label>
                        <textarea name="mission" class="form-control" rows="2"><?php echo htmlspecialchars($about_us['mission'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($about_us['contact_email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($about_us['contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($about_us['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_about" class="btn btn-primary w-100">Update Content</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card form-card mb-4">
            <div class="card-header">
                <h2><i class="fas <?php echo $edit_official ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i> <?php echo $edit_official ? 'Edit Official Profile' : 'Add Official Profile'; ?></h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_official): ?>
                        <input type="hidden" name="official_id" value="<?php echo $edit_official['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $edit_official ? htmlspecialchars($edit_official['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" value="<?php echo $edit_official ? htmlspecialchars($edit_official['position']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea name="bio" class="form-control" rows="3" required><?php echo $edit_official ? htmlspecialchars($edit_official['bio']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <?php if($edit_official && !empty($edit_official['image_path'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($edit_official['image_path']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                                <small class="text-muted d-block">Current image</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*" onchange="openCropper(this, 'official')">
                        <small class="text-muted">Click to select image, then crop to fit perfectly</small>
                    </div>
                    <?php if ($edit_official): ?>
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_official" class="btn btn-primary flex-grow-1">Update Official</button>
                            <a href="manage_about_us.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="add_official" class="btn btn-primary w-100">Add Official</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="card table-card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Official Profiles</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Official</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($officials && $officials->num_rows > 0): ?>
                            <?php while($official = $officials->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($official['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($official['image_path']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($official['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($official['position']); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="manage_about_us.php?edit_official_id=<?php echo $official['id']; ?>" class="btn btn-info btn-sm text-white">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Delete this profile?')" style="display:inline;">
                                            <input type="hidden" name="official_id" value="<?php echo $official['id']; ?>">
                                            <button type="submit" name="delete_official" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No profiles added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentImageType = '';

function openCropper(input, imageType) {
    if (input.files && input.files[0]) {
        currentImageType = imageType;
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Store image data in session storage
            sessionStorage.setItem('tempImage', e.target.result);
            sessionStorage.setItem('imageType', imageType);
            sessionStorage.setItem('inputName', input.name);
            sessionStorage.setItem('returnUrl', window.location.href);
            
            // Open cropper in new window without URL parameters
            const cropperWindow = window.open('image_cropper.php', 'cropper', 'width=800,height=600');
        };
        
        reader.readAsDataURL(file);
    }
}

function showCroppedPreview(imageData, imageType) {
    // Create preview container if it doesn't exist
    let previewContainer = document.querySelector('.cropped-preview-' + imageType);
    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.className = 'cropped-preview-' + imageType + ' mt-2 p-2 border rounded';
        previewContainer.innerHTML = '<small class="text-muted d-block mb-1">Cropped image preview:</small>';
        previewContainer.style.backgroundColor = '#f8f9fa';
        
        // Insert after the file input
        const fileInput = document.querySelector('input[name="' + (imageType === 'agency' ? 'agency_image' : imageType === 'office' ? 'office_image' : 'image') + '"]');
        if (fileInput) {
            fileInput.parentNode.insertBefore(previewContainer, fileInput.nextSibling);
        }
    }
    
    // Update preview image
    const previewImg = document.createElement('img');
    previewImg.src = imageData;
    previewImg.style.maxWidth = '200px';
    previewImg.style.maxHeight = '150px';
    previewImg.style.borderRadius = '8px';
    
    previewContainer.innerHTML = '<small class="text-muted d-block mb-1">Cropped image preview:</small>';
    previewContainer.appendChild(previewImg);
}

// Handle cropped image when returning from cropper
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have a cropped image from session storage
    const croppedImage = sessionStorage.getItem('croppedImage');
    const imageType = sessionStorage.getItem('imageType');
    const inputName = sessionStorage.getItem('inputName');
    
    if (croppedImage && imageType && inputName) {
        // Clear session storage
        sessionStorage.removeItem('croppedImage');
        sessionStorage.removeItem('imageType');
        sessionStorage.removeItem('inputName');
        
        // Convert base64 to blob and create file input
        fetch(croppedImage)
            .then(res => res.blob())
            .then(blob => {
                const file = new File([blob], 'cropped_image.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                
                // Find the original input and replace it
                const originalInput = document.querySelector('input[name="' + inputName + '"]');
                if (originalInput) {
                    // Create new file input with cropped image
                    const newInput = document.createElement('input');
                    newInput.type = 'file';
                    newInput.name = inputName;
                    newInput.files = dataTransfer.files;
                    newInput.className = 'form-control';
                    newInput.setAttribute('data-cropped', 'true');
                    
                    // Replace original input
                    originalInput.parentNode.replaceChild(newInput, originalInput);
                    
                    // Show preview
                    showCroppedPreview(croppedImage, imageType);
                }
            });
    }
});

// Handle form submission with cropped images
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Check if we have cropped images to handle
            const croppedImages = document.querySelectorAll('[data-cropped="true"]');
            if (croppedImages.length > 0) {
                // Let the normal form submission handle the cropped images
                console.log('Submitting form with cropped images');
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>