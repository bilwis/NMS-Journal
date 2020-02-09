window.addEventListener('DOMContentLoaded', listen, false);

function listen()
{
    document.getElementById('screenshot').addEventListener('change', fileChange, false);
}

function fileChange(e) {
    document.getElementById('full_img').value = '';
    document.getElementById('header_img').value = '';
    document.getElementById('thumb_img').value = '';
    
    //document.getElementById('submit_button').style.visibility = 'hidden';

    var file = e.target.files[0];

    if (file.type == "image/jpeg" || file.type == "image/png") {
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

                    if (file.type == "image/jpeg") {
                        var dataURL = canvas.toDataURL("image/jpeg", 1.0);
                    } else {
                        var dataURL = canvas.toDataURL("image/png");
                    }
                    return dataURL;
                }

                document.getElementById('full_img').value = resize(5000);
                document.getElementById('header_img').value = resize(1000);
                document.getElementById('thumb_img').value = resize(250);
                document.getElementById('submit_button').style.visibility = 'visible';
            }
            image.src = readerEvent.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('screenshot').value = '';
        alert('Please only select images in JPG- or PNG-format.');
    }
}
