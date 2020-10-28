<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
        <title>Newsletter-Versand</title>
    </head>
    <body class="p-3 mb-2 bg-secondary text-white">
        <div class="container">
            <?php if(!empty($statement)): ?>
                    <script>
                            alert("<?php echo filterHtmlTags($statement); ?>");
                    </script>
            <?php endif; ?>
            <div style="margin-bottom: 100px">
                <form method="POST" style="float:left;margin-right: 50px" action="logout">
                    <button type="submit"  class="btn btn-primary btn-lg">
                        Logout  
                    </button>
                </form>
                <form method="POST" style="float:left;margin-right: 50px" action="dispatch">
                    <button type="submit" name="data" class="btn btn-primary btn-lg">Datei hochladen</button>
                </form>
                <form method="POST" action="dispatch">
                    <button type="submit" name="dispatch" class="btn btn-primary btn-lg">Versand</button>
                </form>
            </div>
            <?php if (isset($_POST['data'])): ?>
                <form enctype="multipart/form-data" action="dispatch" method="POST">
                    <p>
                        <strong>
                            Laden Sie hier Ihre HTML-Datei hoch:
                        </strong>
                    </p>
                    <p>
                        <input name="htmlfile" type="file" accept="text/html" />
                        <input type="submit" value="Hochladen" />
                    </p>
                </form>
                <form enctype="multipart/form-data" action="dispatch" method="POST">
                    <p>
                        <strong>
                            Laden Sie hier Ihre CSV-Datei hoch:
                        </strong>
                    </p>
                    <p>
                        <input name="csvfile" type="file" accept="text/csv" />
                        <input type="submit" value="Hochladen" />
                    </p>
                </form>
            <?php endif; ?>
            <?php if(isset($_POST['dispatch']) || !empty($_POST['numbersOfTemplates'])): ?>
                <form style="margin-bottom:50px" method="POST" action="dispatch">
                    Wie viele templates wollen Sie hinzufügen? 
                    <input type="number" name="numbersOfTemplates">
                    <input type="submit" name="templateShow" value="Hinzufügen" />
                </form>
                <form method="POST" action="dispatch">
                    <?php if(!empty($_POST['numbersOfTemplates'])): ?>
                        <?php for ($i = 1; $i <= $_POST['numbersOfTemplates']; $i++): ?>
                            <p>
                                Wählen Sie Ihr Templates aus:
                                <select style="margin-bottom:50px" name="templates<?php echo filterHtmlTags($i); ?>">
                                    <?php foreach($templates AS $template): ?>
                                        <option>
                                            <?php echo filterHtmlTags($template) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                        <?php endfor; ?>   
                    <?php endif;  ?>
                    <p>
                        Bitte geben Sie hier Ihre E-Mail ein (nur bei Testversand nötig!):
                    </p>
                    <p>
                        <input name="receiveremail" type="email" />
                    </p>
                    <p>
                        Bitte geben Sie hier Ihren Namen ein (nur bei Testversand nötig!):
                    </p>
                    <p>
                        <input name="receivername" type="text" />
                    </p>
                    <p>
                        Bitte geben Sie hier den Betreff ein:
                    </p>
                    <p>
                        <input name="subject" type="text" />
                    </p>
                    <p> 
                    <p>
                        <input name="trialDispatch" type="checkbox" />
                        Test-Versand
                    </p>
                    <p>
                        <input name="customerDispatch" type="checkbox" />
                        KundenVersand
                    </p>
                    <p>
                        <input name="dispatch" type="submit" value="Bestätigen" />
                    </p>
                </form>
            <?php endif; ?>
        </div>

    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>

    <!-- Option 2: jQuery, Popper.js, and Bootstrap JS
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    -->
  </body>
</html>