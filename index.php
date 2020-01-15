<?php 
    include 'fn/files.php';
    $files = new file();
    
    if(!empty($_GET['dir'])){
        $dir = $_GET['dir'];
    } else {
        $dir = '';
    }

    // Esta linea solo se activa para crear el primer directorio con un usuario por defecto, si ya se están manejando sesiones es necesario eliminar esta línea y usa mkDir() en el registro de usuario para crear una carpeta por usuario.
    // $files->mkDir('default','default');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Storehouse - Documents</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/flexboxgrid.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

    <!-- ESTAS MODALS DEBERÁN INCLUIRSE AL INICIO DE TÚ CODIGO HTML -->
    <!-- Para identificar las diferentes modals solo deberá cambiarse el id del div principal manteniendo el prefijo "modal-" -->
    <?php include 'modals.html'; ?>

    <div class="container">
        <div class="row center-xs">
            <div class="col-xs-12 col-sm-9 col-md-6">
                <!-- AQUÍ INICIA LA VISTA DE LOS ARCHIVOS. Puede ser ubicado en cualquier contenedor, para el estilo es importante agregar flexboxgrid.min.css ya que esto crea la grilla -->
                <div class="container-fluid">
                    <div class="row middle-xs start-xs">
                        <div class="col-xs-8 col-sm-10">
                            <h2>Documentos</h2>
                        </div>
                        <div class="col-xs-2 col-sm-1">
                            <button data-modal="toogleModal" data-type="addFiles">add</button>
                        </div>
                        <div class="col-xs-2 col-sm-1">
                            <!-- <button onclick="query({'query':'createDir','name':'lsls','actual':'<?php echo $dir; ?>'});">folder</button> -->
                            <button data-modal="toogleModal" data-type="addFolder">folder</button>
                        </div>
                    </div>

                    <!-- DIRECTORIOS -->
                    <div class="row" id="foldersContainer">
                        <?php $files->getDirs('default',$dir); ?>
                    </div>
                    
                    <!-- ELEMENTOS -->
                    <div class="row" id="filesContainer">
                        <?php $files->getDocuments('default',$dir); ?>
                    </div>
                </div>
                <!-- AQUÍ TERMINA LA VISTA -->
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>