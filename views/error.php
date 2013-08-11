<!DOCTYPE html>
<html>
<head>
<title><?php echo $title; ?></title>
<meta charset="utf-8">
<style type="text/css">
@charset "utf-8";

html {
    color: #444333;
    background: #fff;
    -webkit-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
    text-rendering: optimizelegibility;
}

body, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6 {
    margin: 0;
    padding: 0;
}

body {
    font: 500 0.875em/1.8 Microsoft Yahei, Hiragino Sans GB, WenQuanYi Micro Hei, sans-serif;
    *width: auto;
    *overflow: visible;
    line-height: 22px;
}

a:hover {
    text-decoration: underline;
}

ins, a {
    text-decoration: none;
}

pre, code {
    font-family: "Courier New", Courier, monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
}

pre {
    background: #f8f8f8;
    border: 1px solid #ddd;
    padding: 1em 1.5em;
}

hr {
    border: none;
    border-bottom: 1px solid #cfcfcf;
    margin-bottom: 10px;
    *color: pink;
    *filter: chroma(color=pink);
    height: 10px;
    *margin: -7px 0 2px;
}

.clearfix:before, .clearfix:after {
    content: "";
    display: table;
}

.clearfix:after {
    clear: both
}

.clearfix {
    zoom: 1
}

.typo p, .typo pre, .typo ul, .typo ol, .typo dl, .typo form, .typo hr, .typo table,
.typo-p, .typo-pre, .typo-ul, .typo-ol, .typo-dl, .typo-form, .typo-hr, .typo-table {
    margin-bottom: 1.2em;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 500;
    *font-weight: 800;
    font-family: Helvetica Neue, Microsoft Yahei, Hiragino Sans GB, WenQuanYi Micro Hei, sans-serif;
    color: #333;
}

.typo h1, .typo h2, .typo h3, .typo h4, .typo h5, .typo h6,
.typo-h1, .typo-h2, .typo-h3, .typo-h4, .typo-h5, .typo-h6 {
    margin-bottom: 0.4em;
    line-height: 1.5;
}

.typo h1, .typo-h1 {
    font-size: 1.8em;
}

.typo h2, .typo-h2 {
    font-size: 1.6em;
}

.typo h3, .typo-h3 {
    font-size: 1.4em;
}

.typo h4, .typo-h4 {
    font-size: 1.2em;
}

.typo h5, .typo h6, .typo-h5, .typo-h6 {
    font-size: 1em;
}

::-moz-selection {
    background: #08c;
    color: #fff;
}

::selection {
    background: #08c;
    color: #fff;
}

body h1 {
    font: 38px/1.8em Hiragino Mincho ProN, STSong, serif!important;
}

#wrapper {
    min-width: 480px;
    padding: 5% 8%;
}

#tagline {
    color: #888;
    font-size: 1em;
    margin: -2em 0 2em;
    padding-bottom: 2em;
}
</style>
</head>
<body class="typo">
<div id="wrapper" class="typo typo-selection">
    <h1><?php echo $title; ?></h1>
    <h2 id="tagline"><?php echo $message; ?></h2>
</div>
</body>
</html>