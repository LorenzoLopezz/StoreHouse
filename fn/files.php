<?php

    class file{

        // Variable de conexión a base de datos
        public $con;
        // Variables de configuración, debería tener como valor el usuario registrado que tiene acceso a los archivos y carpetas
        private $usuario_id;
        // Constructor en el que se definirán las credenciales de la BD y el directorio raíz
        public function __construct(){
            //Este usuario se ha establecido como prueba, se puede eliminar después de agregar una sesión
            $this->usuario_id = "defaultUser";

            define('SERVER', 'localhost');
            define('USER', 'root');
            define('PASSWORD', '');
            define('DB', 'archivos');
            define('TABLE', 'elementos');
            define('RAIZ', 'files/');
            // Conexión MySQL, podría usarse otro tipo de BD, como SQLite, pero se tendrá que cambiar todo el módelo
            $this->con = new mysqli(SERVER,USER,PASSWORD,DB);
        }

        // Método para agregar archivos, ya sea uno o varios, recibe tres parametros
        // 1 - Árbol de donde se coloran los archivos, se usará para separar los archivos por usuarios, ya que cada usuario debería tener su propio directorio en donde se almacenan solamente sus archivos
        // 2 - Dirección en el que se ubican los archivos
        // 3 - Este parametro recibirá todos los archivos a través de una arreglo $_FILES
        public function addFile($tree_id,$dir,$file){

            $direccion = RAIZ.$tree_id.'/'.$dir;

            //Recorre el arreglo de archivos y los mueve al servidor, si el archivo es copiado entonces se guarda el registro en la BD, si no, simplemente termina el proceso
            foreach ($file['files']['tmp_name'] as $key => $tmp_name) 
            {
                if ($file['files']['name'][$key]) {
                    $name = $file['files']['name'][$key];
                    $temp = $file['files']['tmp_name'][$key];
                    $size = $file['files']['size'][$key];            
                    
                    $user = $this->usuario_id;

                    $ex = explode(".",$name);
                    $type = $ex[count($ex)-1];

                    $target_path = '../'.$direccion.'/'.$name;

                    if (move_uploaded_file($temp, $target_path)) {
                        $this->con->query("INSERT INTO ".TABLE."(dir,nombre,tipo,size,file_tree_id,file_usuario_id) VALUES('$direccion','$name','$type','$size','$tree_id','$user')");
                    }
                }
            };	
        }

        // Este método crea los directorios para cada usuario y recibe dos parametros. Esto es importante si se trata con usuarios para separar los archivos de cad usuario y protegerlos.
        // Param 1 - $tree_id es el id del usuario, es llamado así porque es el ID del árbol inicial de archivos
        // Param 2 - $name Es el nombre del directorio, normalmente debería ser un código unico o el ID del usuario para que se pueda identificar como único.

        // IMPORTANTE: Este método viene ligado al registro de usuarios, de otro modo no usarlo. El id de este directorio debe guardarse en el registro del usuario para indexarlo posteriormente.
        public function mkDir($tree_id,$name){

            $direct = RAIZ.$name;

            $sql = $this->con->query("SELECT * FROM ".TABLE." WHERE nombre='$name'");
            $a = $sql->fetch_array();

            if(!$a['archivo_id']){
                $dir = RAIZ;
                $user = $this->usuario_id;
                $type = "directorio";
                $size = 0;
    
                if(mkdir($direct,0777)){
                    $this->con->query("INSERT INTO ".TABLE."(dir,nombre,tipo,size,file_tree_id,file_usuario_id) VALUES('$dir','$name','$type','$size','$tree_id','$user')");
                    
                    return true;
                }
            } else {
                return false;
            }
        }

        // A través de este método se crearán los directorios para almacenar archivos, los parametros de este método llevan relación con el usuario que está conectado actualmente al servicio.
        // 1 - $tree_id es el ID/CODIGO de la carpeta que le pertenece al usuario en sesión
        // 2 - $dir es la dirección en la que se creará el directorio, en la carpeta raíz, como subcarpeta, etc.
        // 3 - $name será el nombre del directorio, será comprobada su existencia, si existe el proceso no se llevará a cabo.
        // 4 - $actual es la ubicación actual del navegador de archivos
        public function mktreeDir($tree_id,$dir,$name,$actual){
            $direct = '../'.$dir.$actual.$name;
            $now = $dir.$actual;

            $sql = $this->con->query("SELECT * FROM ".TABLE." WHERE nombre='$name' AND dir='$now'");
            $a = $sql->fetch_array();

            if(!$a['archivo_id']){
                $user = $this->usuario_id;
                $type = "directorio";
                $size = 0;
    
                if(mkdir($direct,0777)){
                    $this->con->query("INSERT INTO ".TABLE."(dir,nombre,tipo,size,file_tree_id,file_usuario_id) VALUES('$now','$name','$type','$size','$tree_id','$user')");

                    $this->getDirs($tree_id,$actual);
                }
            }
        }

        // Para eliminar los archivos usaremos el metodo deleteFile al cual se le específica el ID del archivo y a la misma vez el folder en el cual se está navegando para obtener una respuesta AJAX y refrescar la lista de archivos.
        public function deleteFile($id,$folder){
            $sql = $this->con->query("SELECT * FROM ".TABLE." WHERE archivo_id='$id'");
            $files = $sql->fetch_array();

            $dif = $files['dir'];
            if($dif === RAIZ.$folder.'/'){
                $redirect = "";
            } else {
                $x = strlen(RAIZ.$folder.'/');
                $y = strlen($files['dir']);
                $z = $x-$y;
                $redirect = substr($files['dir'],$z);
            }

            $dir = '../'.$files['dir'].$files['nombre'];

            if(unlink($dir)){
                $this->con->query("DELETE FROM ".TABLE." WHERE archivo_id='$id'");

                $this->getDocuments($folder,$redirect);
            }
        }

        // Este método es el más complejo de explicar, pues es todo un algoritmo para eliminar un árbol completo de archivos, lo único que necesitaremos especificar es el ID del directorio a eliminar. 
        public function deleteFolder($id,$dir){
            $conx = new mysqli(SERVER,USER,PASSWORD,DB);
            $sql = $conx->query("SELECT * FROM ".TABLE." WHERE archivo_id='$id'");
            $directorio = $sql->fetch_array();
            $location = $directorio['dir'];
            $folder = $directorio['file_tree_id'];
            $dir = '../'.$location.$directorio['nombre'].'/';
            $d = $location.$directorio['nombre'].'/';

            $sql2 = $conx->query("SELECT * FROM ".TABLE." WHERE dir LIKE '%".$d."%' AND tipo!='directorio' ORDER BY fecha DESC");

            while($others = $sql2->fetch_array()){
                $a = $others['nombre'];

                $aa = '../'.$others['dir'].$others['nombre'];
                $id = $others['archivo_id'];
                if(unlink($aa)){
                    $conx->query("DELETE FROM ".TABLE." WHERE archivo_id='$id'");
                }
            }

            $sql2 = $conx->query("SELECT * FROM ".TABLE." WHERE dir LIKE '%".$d."%' AND tipo='directorio' ORDER BY fecha DESC");

            while($others = $sql2->fetch_array()){
                $o = $others['nombre'];

                $oo = '../'.$others['dir'].$others['nombre'];
                $id = $others['archivo_id'];
                if(rmdir($oo)){
                    $conx->query("DELETE FROM ".TABLE." WHERE archivo_id='$id'");
                }
            }

            if(rmdir($dir)){
                $id_carpeta = $directorio['archivo_id'];
                $conx->query("DELETE FROM ".TABLE." WHERE archivo_id='$id_carpeta'");

                if($location === RAIZ.$folder.'/'){
                    $redirect = "";
                } else {
                    $x = strlen(RAIZ.$folder.'/');
                    $y = strlen($location);
                    $z = $x-$y;
                    $redirect = substr($location,$z);
                }

                $this->getDirs($folder,$redirect);
            }
        }

        // Obtener todos los directorios. Se obtendrán los directorios con su interfaz para insertar en el DOM.
        // El único parametro necesario es el ID de la carpeta raíz del usuario.
        public function getDirs($tree_id,$dir){
            $tree = RAIZ.$tree_id.'/'.$dir;

            $sql = $this->con->query("SELECT * FROM ".TABLE." WHERE file_tree_id='$tree_id' AND dir='$tree' AND estado='1' AND tipo='directorio' ORDER BY fecha DESC");

            while($folder = $sql->fetch_array()){
                $id_folder = $folder['archivo_id'];
                $direccion = $folder['dir'];
                $nombre = $folder['nombre'];

                echo '
                    <div class="col-xs-6 col-sm-4 col-md-3" data-id-boxfolder="'.$id_folder.'">
                        <div style="background: #f5f5f5; padding: 10px; border-radius: 10px;margin: 5px 0px;">
                            <img src="img/folder.png" width="100%">
                            <a href="?dir='.$dir.$nombre.'/"><p>'.$nombre.'</p></a>
                            <div>
                                <button onclick="query({\'query\':\'deleteFolder\',\'id\':\''.$id_folder.'\',\'actual\':\''.$direccion.'\'})"><i class="material-icons">delete</i></button>
                            </div>
                        </div>
                    </div>
                ';
            }
        }

        // Obtener todos los archivos que se encuentran en la carpeta actual. Se necesitará la dirección actual.
        public function getDocuments($folder,$dir){
            $d = RAIZ.$folder.'/'.$dir;
            $sql = $this->con->query("SELECT * FROM ".TABLE." WHERE file_tree_id='$folder' AND dir='$d' AND tipo!='directorio' AND dir='$d'");
            
            while($archivo = $sql->fetch_array()){

                $id_archivo = $archivo['archivo_id'];
                $dir_file = $archivo['dir'];
                $size = substr(($archivo['size']*0.0000009537),0,4).' MB';

                $nombre = $archivo['nombre'];
                $len_name = strlen($nombre);
                if ($len_name >= 15) {
                    $nombre = substr($nombre, 0,16)."..."; 
                } else {
                    $nombre = $nombre; 
                }

                $l = strlen($folder);

                if($dir_file == RAIZ.$folder.'/'){
                    $dir_file_short = $archivo['nombre'];
                } else {
                    $dir_file_short = substr($dir_file,(7+$l)).$archivo['nombre'];
                }

                $ic = $archivo['tipo'];

                $icon = "default.png";

                if ( $ic === "jpeg" || $ic === "jpg" ) {
                    $icon = "jpeg.png";
                }
                if( $ic === "png" ){
                    $icon = "png.png";
                }
                if ( $ic === "rar" ) {
                    $icon = "rar.png";
                }
                if ( $ic === "zip" ) {
                    $icon = "zip.png";
                }
                if ( $ic === "mp3" ) {
                    $icon = "mp3.png";
                }
                if ( $ic === "docx" || $ic === "doc" ) {
                    $icon = "doc.png";
                }
                if ( $ic === "xlsx" ) {
                    $icon = "excel.png";
                }
                if ( $ic === "pdf" ) {
                    $icon = "pdf.png";
                }
        
                echo '
                    <div class="col-xs-6 col-sm-4 col-md-3" data-id-boxfile="'.$id_archivo.'">
                        <div style="background: #f5f5f5; padding: 10px; border-radius: 10px;margin: 5px 0px;">
                            <img src="img/'.$icon.'" width="100%">
                            <a href="'.RAIZ.'default/'.$dir_file_short.'" target="_blank"><p>'.$nombre.'</p></a>
                            <p>'.$size.'</p>
                            <div class="row">
                                <div class="col-xs-4"><button onclick="query({\'query\':\'deleteFiles\',\'id\':\''.$id_archivo.'\'});"><i class="material-icons">delete</i></button></div>
                                <div class="col-xs-4"><a href="'.RAIZ.'default/'.$dir_file_short.'" download><button><i class="material-icons">save</i></button></a></div>
                                <div class="col-xs-4"><button onclick="shareFile(\'http://localhost/portfolio_personal/proyects/familyDrive/'.RAIZ.'default/'.$dir_file_short.'\');"><i class="material-icons">share</i></button></div>
                            </div>
                        </div>
                    </div>
                ';
        
            }
        }
    }

?>