<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail from CIMIM</title>
</head>
<body>
    <h1> {{ $details['title'] }} </h1>
    <p> {{ $details['body'] }} </p> 

    <form class="login100-form validate-form" method="POST" action="">
        @csrf
        
        <div class="input100">                       
            <button type="submit" class="btn btn-primary">
                Click here
            </button>
        </div>
        
    </form>

    <p>Thank you</p>
</body>
</html>