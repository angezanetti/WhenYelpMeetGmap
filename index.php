<?php


// Enter the path that the oauth library is in relation to the php file
require_once ('lib/OAuth.php');

// For example, request business with id 'the-waterboy-sacramento'
$unsigned_url = "http://api.yelp.com/v2/search?location=Lille+wazemmes";

// Set your keys here
$consumer_key = "";
$consumer_secret = "";
$token = "";
$token_secret = "";

// Token object built using the OAuth library
$token = new OAuthToken($token, $token_secret);

// Consumer object built using the OAuth library
$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

// Yelp uses HMAC SHA1 encoding
$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

// Build OAuth Request using the OAuth PHP library. Uses the consumer and token object created above.
$oauthrequest = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $unsigned_url);
// Sign the request
$oauthrequest->sign_request($signature_method, $consumer, $token);

// Get the signed URL
$signed_url = $oauthrequest->to_url();

// Send Yelp API Call
$ch = curl_init($signed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch); // Yelp response
curl_close($ch);

?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0; padding: 0 }
      #map-canvas { height: 95% }
    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=PutYourKeyHere&sensor=false">
    </script>
    
    <script type="text/javascript">

        var map = null;
        var geocoder = new google.maps.Geocoder();
        var info_window = new google.maps.InfoWindow();
        var bounds = new google.maps.LatLngBounds();
            
        var image = 'img/maison.png';
        
        function geocodeAddress(markers) {
            var address = markers.location.address[0];
            var city = markers.location.city;
            var address_google_map = address + ', ' + city;
            var picture = '<img src ="' + markers.image_url + '" />';
            var rating = '<img src ="' + markers.rating_img_url + '" />';
            var name = '<b>' + markers.name + '</b>';

            var categories_array = markers.categories;
            var categories = '';

            for (var c = 0; c < categories_array.length; c++ ) {
                    categories += categories_array[c][1] + ',';
                    console.log(categories);
            }

            var info_text = name + '<br />' + categories + '<br />' + rating + '<br />' + address + '<br />' + city + '<br />' + picture;
            
            geocoder.geocode ({'address': address_google_map}, function (results, status) {
              if (status == google.maps.GeocoderStatus.OK) {
                createMarker(results[0].geometry.location, info_text);
              } else { 
                console.log("geocode of "+ address +" failed:"+status);
              }
            });
        }

        function createMarker(latlng, html) {
            var marker = new google.maps.Marker ({
                map: map, 
                position: latlng,
                icon: image
            });
            google.maps.event.addListener(marker, 'click', function() {
                info_window.setContent(html);
                info_window.open(map, marker);
            });
            bounds.extend(latlng);
        }
        
        
        function initialize() {
          
            var json = JSON.parse (<?php echo json_encode($data); ?> );
            console.log(json);
            var mapOptions = {
              center: new google.maps.LatLng(json.region.center.latitude, json.region.center.longitude ),
              zoom: 15,
              mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);

            google.maps.event.addListener (map, 'click', function () {
                info_window.close();
            });

            var markers = json.businesses;
            var geocoder = new google.maps.Geocoder();
            for (var i = 0; i < markers.length; i++) {
                geocodeAddress(markers[i]);
            }
            google.maps.event.addListenerOnce(map, 'idle', function() {map.fitBounds(bounds);});
        }
        google.maps.event.addDomListener(window, 'load', initialize);
      
   </script>
  </head>
  <body>
      <div id="map-canvas"></div>
        <img src="http://s3-media1.ak.yelpcdn.com/assets/2/www/img/1589098a542f/developers/Powered_By_Yelp_White.png" alt="YelpLogo" />
  </body>
</html>

