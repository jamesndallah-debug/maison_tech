<!DOCTYPE html>
<html>
<head>
    <title>Image Cropper - Maison Tech</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
        }
        .image-container {
            max-width: 100%;
            margin-bottom: 20px;
        }
        .preview-container {
            width: 300px;
            height: 300px;
            border: 2px solid #ddd;
            margin: 20px auto;
            overflow: hidden;
        }
        .cropper-container {
            max-width: 100%;
            margin: 20px 0;
        }
        .btn-crop {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-crop:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Crop Image</h2>
        
        <div class="image-container">
            <img id="image" style="max-width: 100%;">
        </div>
        
        <div class="cropper-container">
            <div class="preview-container">
                <img id="preview" style="max-width: 100%;">
            </div>
        </div>
        
        <div class="text-center mt-3">
            <button class="btn-crop" onclick="cropImage()">Crop & Apply</button>
            <button class="btn btn-secondary ms-2" onclick="cancelCrop()">Cancel</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        let cropper;
        
        window.onload = function() {
            // Get image data from session storage
            const imageData = sessionStorage.getItem('tempImage');
            const returnUrl = sessionStorage.getItem('returnUrl');
            
            if (imageData) {
                const image = document.getElementById('image');
                image.src = imageData;
                
                cropper = new Cropper(image, {
                    aspectRatio: NaN, // Free aspect ratio
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
                
                // Update preview
                cropper.addEventListener('crop', function(e) {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    canvas.width = e.detail.width;
                    canvas.height = e.detail.height;
                    
                    ctx.drawImage(e.detail.canvas, 0, 0);
                    
                    document.getElementById('preview').src = canvas.toDataURL('image/jpeg', 0.8);
                });
            } else {
                // No image data, redirect back
                window.location.href = returnUrl || 'manage_about_us.php';
            }
        };
        
        function cropImage() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 800,
                    maxHeight: 600,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                
                canvas.toBlob(function(blob) {
                    // Convert to base64 and store in session
                    const reader = new FileReader();
                    reader.onloadend = function() {
                        sessionStorage.setItem('croppedImage', reader.result);
                        window.location.href = sessionStorage.getItem('returnUrl') || 'manage_about_us.php';
                    };
                    reader.readAsDataURL(blob);
                }, 'image/jpeg', 0.8);
            }
        }
        
        function cancelCrop() {
            window.location.href = sessionStorage.getItem('returnUrl') || 'manage_about_us.php';
        }
    </script>
</body>
</html>
