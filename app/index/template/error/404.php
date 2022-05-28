<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>404 Not Found</title>
<style type="text/css">
p a {
    font-size: 1em;
    line-height: 2em;
    border: solid #ccc 1px;
    display: block;
    padding: 0 10px;
    text-decoration: none;
}

p a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>
<h1>404 Not Found</h1>
<hr>

<h4>没有找到此页面：</h4>
<p><a href="<?=$request_uri?>"><?=$request_uri?></a></p>
<h4><a href="/">返回首页</a></h4>

<hr>
<footer title="<?=__FILE__?>">
<a href="https://github.com/wuding/magic-cube">MagicCube</a> Version <?=$version?>
</footer>
</body>
</html>
