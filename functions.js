var imagesBucketName = document_obj.bucket_name;
var thumbsBucketName = document_obj.bucket_name + '-resized';
var bucketRegion = document_obj.bucket_region;
var IdentityPoolId = document_obj.identity_pool_id;

AWS.config.update({
    region: bucketRegion,
    credentials: new AWS.CognitoIdentityCredentials({
        IdentityPoolId: IdentityPoolId
    })
});

var s3ImageThumbs = new AWS.S3({
    apiVersion: "2006-03-01",
    params: { Bucket: thumbsBucketName }
});

function viewAlbum() {
    s3ImageThumbs.listObjects({
        MaxKeys: 1000,
    }, function (err, data) {

        if (err) {
            return alert("There was an error viewing your album: " + err.message);
        }

        var href = this.request.httpRequest.endpoint.href;
        var bucketUrl = href + thumbsBucketName;

        // filter out invalid photos
        var filteredPhotos = data.Contents.filter(function (photo) {
            var size = photo.Size;

            if (size === 0) {
                return false;
            }

            return true;
        });

        // sort by last modified
        filteredPhotos.sort((a, b) => new Date(b.LastModified) - new Date(a.LastModified));

        // on keep x number of items based on config in UI
        filteredPhotos = filteredPhotos.slice(0, document_obj.number_of_images);

        // create html for each image
        photos = filteredPhotos.map(function (photo) {
            var photoKey = photo.Key;
            var photoUrl = bucketUrl + '/' + encodeURIComponent(photoKey);
            var photoOriginalUrl = photoUrl.replace('-resized', '');

            // using Responsive Lightbox & Gallery plugin for lightbox
            return getHtml([
                "<li style='margin-bottom: 5px'>",
                '<a href="' + photoOriginalUrl + '" rel="lightbox">',
                '<img src="' + photoUrl + '"/>',
                "</a>",
                "</li>"
            ]);
        });

        var htmlTemplate = [
            getHtml(photos),
        ];

        document.getElementById("app").innerHTML = getHtml(htmlTemplate);
    });
}

/**
 * @todo only retry so many times to find thumbnails
 * @param {*} files 
 */
function addPhotos(files) {
    var totalImages = files.length;
    var uploadedImages = 0;
    var thumbnailsFound = 0;
    var timeoutIds = [];
    var progressText = document.getElementById('progress-text');
    var progressBar = document.getElementById('progress-bar');

    var pollForThumb = function (file) {
        var http = new XMLHttpRequest();
        http.open('HEAD', file.Location.replace(imagesBucketName, thumbsBucketName));
        http.onreadystatechange = function () {
            if (this.readyState == this.DONE && this.status == 200) {
                clearInterval(timeoutIds[file.ETag]);
                thumbnailsFound++;

                if (thumbnailsFound == totalImages) {
                    resetProgress();
                    viewAlbum();
                }
            }
        };

        http.send();
    };

    var success = function (file) {
        uploadedImages++;
        progressBar.value = uploadedImages / totalImages * 100;

        if (progressBar.value == 100) {
            progressText.innerHTML = 'Waiting for thumbnails.';
        }

        timeoutIds[file.ETag] = setInterval(pollForThumb, 1000, file);
    };

    var failed = function (error) {
        console.log(error);
    };

    var resetProgress = function () {
        progressText.innerHTML = 'Upload progress:';
        progressBar.value = 0;
        progressBar.parentElement.classList.add('hidden');
    }

    // init UI
    resetProgress();
    progressBar.parentElement.classList.remove('hidden');

    // start adding photos
    files.forEach(file => {
        addPhoto(file, success, failed);
    });
}

function addPhoto(file, success, failed) {
    var fileName = file.name.replace(/[^\w\.]/gi, '_');
    var photoKey = fileName;

    // create new file with the new clean name
    var newFile = new File([file], fileName, { type: file.type });

    // Use S3 ManagedUpload class as it supports multipart uploads
    var upload = new AWS.S3.ManagedUpload({
        params: {
            Bucket: imagesBucketName,
            Key: photoKey,
            Body: newFile,
            ACL: "public-read"
        }
    });

    var promise = upload.promise();

    promise.then(success, failed);
}

function getHtml(template) {
    return template.join('\n');
}

viewAlbum();

// admin functions
function dragOverHandler(ev) {
    console.log('File(s) in drop zone');

    // Prevent default behavior (Prevent file from being opened)
    ev.preventDefault();
}

function dropHandler(ev) {
    console.log('File(s) dropped');

    // Prevent default behavior (Prevent file from being opened)
    ev.preventDefault();

    var files = [];

    if (ev.dataTransfer.items) {
        // Use DataTransferItemList interface to access the file(s)
        for (var i = 0; i < ev.dataTransfer.items.length; i++) {
            // If dropped items aren't files, reject them
            if (ev.dataTransfer.items[i].kind === 'file') {
                var file = ev.dataTransfer.items[i].getAsFile();

                if (validateImageFile(file)) {
                    files.push(file);
                }
            }
        }
    } else {
        // Use DataTransfer interface to access the file(s)
        for (var i = 0; i < ev.dataTransfer.files.length; i++) {
            var file = ev.dataTransfer.files[i];

            if (validateImageFile(file)) {
                files.push(file);
            }
        }
    }

    if (files.length > 0) {
        addPhotos(files);
    }
}

function validateImageFile(file) {
    if (typeof file['type'] !== 'undefined' && file.type.startsWith('image/')) {
        return true;
    }

    return false;
}

let dropArea = document.getElementById('drop-area');

;['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
})

    ;['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    })

function highlight(e) {
    dropArea.classList.add('highlight');
}

function unhighlight(e) {
    dropArea.classList.remove('highlight');
}
