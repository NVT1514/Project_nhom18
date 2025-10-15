<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bản đồ Google</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .main-content {
            flex: 1;
            padding: 30px;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script>
        function initMap() {
            var location = {
                lat: 21.0285,
                lng: 105.804
            }; // Vị trí mặc định (Hà Nội)
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: location
            });
            var marker = new google.maps.Marker({
                position: location,
                map: map
            });
        }
    </script>
</head>

<body onload="initMap()">
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <h2>Bản đồ Google</h2>
            <div id="map"></div>
        </div>
    </div>
</body>

</html>