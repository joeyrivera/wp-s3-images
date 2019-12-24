var bucketRegion = document_obj.bucket_region;
var imagesBucketName = document_obj.bucket_name;
var thumbsBucketName = document_obj.bucket_name + '-resized';
var queueURL = document_obj.queue_url;


// setup aws defaults
AWS.config.update({
    region: bucketRegion,
    credentials: new AWS.CognitoIdentityCredentials({
        IdentityPoolId: 'us-east-1:2c610da5-5199-4d5a-89eb-89ffb7852e04'
    })
});

// declare s3 service
var s3ImageThumbs = new AWS.S3({
    apiVersion: "2006-03-01",
    params: { 
        Bucket: thumbsBucketName
    }
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
        var filteredPhotos = data.Contents.filter(photo => photo.Size);

        // sort by last modified
        filteredPhotos.sort((a, b) => new Date(b.LastModified) - new Date(a.LastModified));

        // on keep x number of items based on config in UI
        filteredPhotos = filteredPhotos.slice(0, document_obj.number_of_images);

        // create html for each image
        photos = filteredPhotos.map(photo => {
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

function getHtml(template) {
    return template.join('\n');
}

viewAlbum();
