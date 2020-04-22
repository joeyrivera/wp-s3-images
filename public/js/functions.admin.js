// drop area element
var dropArea = document.getElementById('drop-area');

// Create an SQS service object
var sqs = new AWS.SQS({ 
    apiVersion: '2012-11-05'
});

// params for sqs service
var sqsParams = {
    QueueUrl: queueURL,
    AttributeNames: [
        "All"
    ],
    MessageAttributeNames: [
        "All"
    ],
    MaxNumberOfMessages: 10,
    WaitTimeSeconds: 1,
    VisibilityTimeout: 30,
};

/**
 * @todo only retry so many times to find thumbnails
 * @param {Array.<File>} files 
 */
function addPhotos(files) {
    var totalImages = files.length;
    var imageKeys = [];
    var uploadedImages = 0;
    var thumbnailsFound = 0;
    var timeoutId = null;
    var progressText = document.getElementById('progress-text');
    var progressBar = document.getElementById('progress-bar');
    var progressThumbsBar = document.getElementById('progress-thumbs-bar');

    var pollForThumb = function () {
        // load sqs messages
        sqs.receiveMessage(sqsParams, function (err, data) {
            if (err) {
                console.log(err, err.stack);
                clearInterval(timeoutId);
                return;
            }

            if (data.Messages.count === 0) {
                clearInterval(timeoutId);
                return;
            }

            data.Messages.forEach(message => {

                var json = JSON.parse(message.Body);
                var index = imageKeys.indexOf(json.Records[0].s3.object.key);
                
                if (index === -1) {
                    return;
                }

                imageKeys.splice(index, 1);
                thumbnailsFound++;
                progressThumbsBar.value = thumbnailsFound / totalImages * 100;

                // delete message
                var params = {
                    QueueUrl: queueURL,
                    ReceiptHandle: message.ReceiptHandle
                };

                sqs.deleteMessage(params, function (err, data) {
                    if (err) {
                        console.log(err, err.stack);
                    } else {
                        //console.log(data);
                    }
                });

                if (thumbnailsFound == totalImages) {
                    clearInterval(timeoutId);
                    resetProgress();
                    viewAlbum();
                }
            });
        });
    };

    var success = function (file) {
        imageKeys.push(file.Key);
        uploadedImages++;
        progressBar.value = uploadedImages / totalImages * 100;

        if (progressBar.value == 100) {
            progressText.innerHTML = 'Waiting for thumbnails.';

            // start checking sqs
            timeoutId = setInterval(pollForThumb, 2000);
        }
    };

    var failed = function (error) {
        console.log(error);
    };

    var resetProgress = function () {
        imageKeys = [];
        uploadedImages = 0;
        thumbnailsFound = 0;
        timeoutIds = [];
        progressText.innerHTML = 'Upload progress:';
        progressBar.value = 0;
        progressThumbsBar.value = 0;
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

/**
 * 
 * @param {File} file 
 * @param {Function} success 
 * @param {Function} failed 
 */
function addPhoto(file, success, failed) {
    var fileName = file.name.replace(/[^\w\.]/gi, '_');

    // create new file with the new clean name
    var newFile = new File([file], fileName, { type: file.type });

    // Use S3 ManagedUpload class as it supports multipart uploads
    var upload = new AWS.S3.ManagedUpload({
        params: {
            Bucket: imagesBucketName,
            Key: fileName,
            Body: newFile,
            ACL: "public-read"
        }
    });

    var promise = upload.promise();

    promise.then(success, failed);
}

/**
 * Stop default drag over behavior
 * 
 * @param {DragEvent}
 */
function dragOverHandler(ev) {
    ev.preventDefault();
}

/**
 * stop default file drop behavior
 * 
 * @param {DropEvent}
 */
function dropHandler(ev) {
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

/**
 * Make sure the file is a valid image type
 * 
 * @param {File} file 
 */
function validateImageFile(file) {
    if (typeof file['type'] !== 'undefined' && file.type.startsWith('image/')) {
        return true;
    }

    return false;
}

/**
 * add drag event listeneres
 */
;['dragenter', 'dragover'].forEach(eventName => {
    if (dropArea === null) {
        return;
    }
    dropArea.addEventListener(eventName, highlight, false);
})

/**
 * add drop event listeneres
 */
;['dragleave', 'drop'].forEach(eventName => {
    if (dropArea === null) {
        return;
    }
    dropArea.addEventListener(eventName, unhighlight, false);
})

/**
 * Add class
 * @param {DragEvent} e 
 */
function highlight(e) {
    dropArea.classList.add('highlight');
}

/**
 * Remove class
 * @param {DragEvent} e 
 */
function unhighlight(e) {
    dropArea.classList.remove('highlight');
}
