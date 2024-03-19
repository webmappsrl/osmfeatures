<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags Detail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 50%;
            margin: 0 auto;

        }

        pre {
            white-space: pre-wrap;
            /* Since CSS 2.1 */
            white-space: -moz-pre-wrap;
            /* Mozilla, since 1999 */
            white-space: -pre-wrap;
            /* Opera 4-6 */
            white-space: -o-pre-wrap;
            /* Opera 7 */
            word-wrap: break-word;
            /* Internet Explorer 5.5+ */
            background-color: #eee;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            color: #333;
        }

        h1 {
            color: #444;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Tags Detail</h1>
        <pre>{{ $tagsPretty }}</pre>
    </div>
</body>

</html>
