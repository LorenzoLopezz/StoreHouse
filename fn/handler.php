<?php
    // Aquí se manejan las peticiones ajax a través de un switch se obtiene el tipo de consulta y se devuelve el resultado. Actualmente se obtienen los resultados en formato HTML, pero podrían obtenerse con un JSON y con JavaScript crear los elementos en el DOM.

    include 'files.php';

    if(!empty($_POST['query'])){

        $query = $_POST['query'];
        $file = new file();

        switch($query){
            case 'addFiles':
                $files = $_FILES;
                $dir = $_POST['actual'];
                $file->addFile('default',$dir,$files);
            break;
            case 'createDir':
                $name = $_POST['name'];
                if(!empty($_POST['actual'])){
                    $actual = $_POST['actual'];    
                } else {
                    $actual = "";
                }
                $file->mktreeDir('default','files/default/',$name,$actual);
            break;
            case 'getFolders':
                $dir = $_POST['actual'];
                $file->getDirs('default/',$dir);
            break;
            case 'getFiles':
                if(!empty($_POST['actual'])){
                    $dir = $_POST['actual'];    
                } else {
                    $dir = "";
                }
                $file->getDocuments('default',$dir);
            break;
            case 'deleteFiles':
                $id = $_POST['id'];
                $file->deleteFile($id,'default');
            break;
            case 'deleteFolder':
                if(!empty($_POST['actual'])){
                    $dir = $_POST['actual'];    
                } else {
                    $dir = "";
                }
                $id = $_POST['id'];
                $file->deleteFolder($id,$dir);
            break;
        }

    } else {
        echo 'No POST';
    }

?>