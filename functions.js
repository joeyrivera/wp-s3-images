var albumBucketName = document_obj.bucket_name;
var bucketRegion = document_obj.bucket_region;
var IdentityPoolId = document_obj.identity_pool_id;

AWS.config.update({
  region: bucketRegion,
  credentials: new AWS.CognitoIdentityCredentials({
    IdentityPoolId: IdentityPoolId
  })
});

var s3 = new AWS.S3({
  apiVersion: "2006-03-01",
  params: { Bucket: albumBucketName }
});

function viewAlbum(albumName) {
  //var albumPhotosKey = encodeURIComponent(albumName) + "/";
  s3.listObjects({
    Prefix: albumName,
    MaxKeys: 1000,
  }, function (err, data) {

    if (err) {
      return alert("There was an error viewing your album: " + err.message);
    }

    // 'this' references the AWS.Response instance that represents the response
    var href = this.request.httpRequest.endpoint.href;
    var bucketUrl = href + albumBucketName + "/";

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
      var photoUrl = bucketUrl + encodeURIComponent(photoKey);
      var photoOriginalUrl = photoUrl.replace('-resized', '');

      return getHtml([
        "<li style='margin-bottom: 5px'>",
        '<a target="_blank" href="' + photoOriginalUrl + '">',
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
viewAlbum(null);