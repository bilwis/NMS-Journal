function handleBlob(blobInfo, success, failure) {

    xhr = new XMLHttpRequest();
    xhr.withCredentials = false;
    xhr.open('POST', '../entry/tinymce_image_upload.php');
    
    
    xhr.onload = function () {
        var json;

        if (xhr.status != 200) {
            failure('HTTP Error: ' + xhr.status);
            return;
        }

        json = JSON.parse(xhr.responseText);

        if (!json || typeof json.location != 'string') {
            failure('Invalid JSON: ' + xhr.responseText);
            return;
        }

        success(json.location);
    };

    var reader = new FileReader();
    reader.onload = function (readerEvent) {
        var image = new Image();
        image.onload = function (imageEvent) {
            function resize(max_size) {
                var w = image.width;
                var h = image.height;

                if (w > h) {
                    if (w > max_size) {
                        h *= max_size / w;
                        w = max_size;
                    }
                } else {
                    if (h > max_size) {
                        w *= max_size / h;
                        h = max_size;
                    }
                }

                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(image, 0, 0, w, h);


                var dataURL = canvas.toDataURL("image/jpeg", 1.0);

                return dataURL;
            }

            formData = new FormData();
            
            formData.append('full_img', resize(5000));
            formData.append('header_img', resize(1000));
            formData.append('thumb_img', resize(250));

            xhr.send(formData);
        }
        image.src = readerEvent.target.result;
    }
    reader.readAsDataURL(blobInfo.blob());

}
