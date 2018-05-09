
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="{{$data->title}}">
    <meta name="description" content="{{$data->desc}}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="{{$data->title}}">
    <meta property="og:description" content="{{$data->desc}}">
    <meta property="og:url" content="{{$data->host1}}/project/details/{{$data->id}}">
    <meta property="og:site_name" content="Novaby">
    <meta property="wb:webmaster" content="" />
    <meta property="og:image" content="{{$data->cover}}" />
    <meta property="og:image:width" content="500" />
    <meta property="og:image:height" content="800" />
    <meta property="image_url" content="{{$data->cover}}" />

    <meta property="article:section" content="3D MODEL">




    <meta property="og:type" content="article" />
    <meta property="article:publisher" content="http://facebook.com/tailwind" />
    <meta property="article:published_time" content="2017-05-21T10:11:06+00:00" />
    <meta property="article:modified_time" content="2017-05-30T21:49:18+00:00" />
    <meta property="og:updated_time" content="2017-05-30T21:49:18+00:00" />
    <meta property="fb:app_id" content="1985165408393100" />

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@novabycompany">
    <meta name="twitter:creator" content="@novabycompany">
    <meta name="twitter:title" content="{{$data->title}}">
    <meta name="twitter:description" content="{{$data->desc}}">
    <meta name="twitter:image" content="{{$data->cover}}">
    <title>{{$data->title}}</title>
</head>
<body>
<p>
    <img src="{{$data->cover}}" />
</p>
</body>
<script>
    var id = {{$data->id}}
        window.location.href='{{$data->host}}project-hall/project-details/'+id
</script>
</html>