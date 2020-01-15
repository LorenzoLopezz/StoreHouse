document.addEventListener('DOMContentLoaded',() => {
    loadModals();
});

function sender(e){

    const files = document.querySelector('#sender');
    const arch = document.querySelector('#files');

    const form = new FormData(files);

    if(arch.files[0]){
        if(confirm('Enviar los siguientes '+arch.files.length+' archivos?')){

            fetch('fn/handler.php',{
                method: 'POST',
                body: form,
                headers: {
                    'Conten-Type' : 'multipart/form-data'
                }
            }).then(
                () => {
                    document.querySelector('#nameFiles').innerHTML = "";
                    arch.value = "";
                    alert('terminado');
                    document.querySelector('#modal-addFiles').className = "container-fluid modal-container-hidden";
                    query({'query':'getFiles','actual':document.querySelector('#actual').value});
                }
            );

        };

    } else {
        alert('Vacío'); 
    };

    event.preventDefault();
};

document.querySelector("#files").onchange = () => {
    const files = document.querySelector('#files');
    const length = files.files.length;

    document.querySelector('#nameFiles').innerHTML = "";

    for (let index = 0; index < length; index++) {
        const node = document.createElement('li');
        const text = document.createTextNode(files.files[index].name);
        node.appendChild(text);

        document.querySelector('#nameFiles').appendChild(node);
    };
};

document.querySelector('#sendFiles').onclick = () => sender();

// A través de esta función se harán todas las consultas para Eliminar archivo, crear directorio, eliminar directorio y para obtener todos los archivos.
function query(query){

    const form = new FormData();
    form.append('query',query.query);

    if(query.id){
        form.append('id',query.id);
    }

    if(query.name){
        form.append('name',query.name);
    }

    if(query.actual){
        form.append('actual',query.actual);
    }

    fetch('fn/handler.php',{
        method: 'POST',
        body: form,
        headers: {
            'Conten-Type' : 'multipart/form-data'
        }
    }).then(
        response => response.text()
    ).then(
        (resp) => {
            query.name || query.query == 'deleteFolder' ? document.querySelector('#foldersContainer').innerHTML = resp : document.querySelector('#filesContainer').innerHTML = resp;
        }
    )

}

document.querySelector('#newFolder').onclick = () => {
    const name = document.querySelector('#folderName_add');
    const dir = name.dataset.dir;
    query({'query':'createDir','name':name.value,'actual':dir});
    name.value = "";
    document.querySelector('#modal-addFolder').className = "container-fluid modal-container-hidden";
};

function loadModals(){
    const btn_modals = document.querySelectorAll('[data-modal]');

    for (let btn = 0; btn < btn_modals.length; btn++) {
        const element = btn_modals[btn];
        element.onclick = () => {
            const type = element.dataset.type;
            const modal = document.querySelector('#modal-'+type);
            switch (type) {
                case 'addFiles':
                    if(modal.className == "container-fluid modal-container") {
                        const arch = document.querySelector('#files');
                        document.querySelector("#nameFiles").innerHTML = "";
                        modal.className = "container-fluid modal-container-hidden";
                        arch.value = "";
                    }else {
                        modal.className = "container-fluid modal-container";
                    } 
                break;
                case 'addFolder':
                    if(modal.className == "container-fluid modal-container"){
                        document.querySelector('#folderName_add').value = "";
                        modal.className = "container-fluid modal-container-hidden";
                    }else{
                        modal.className = "container-fluid modal-container";
                    }
                break;
                case 'shareFile':
                    if(modal.className == "container-fluid modal-container"){
                        document.querySelector('#fileNameDownload').value = "";
                        modal.className = "container-fluid modal-container-hidden";
                    }else{
                        modal.className = "container-fluid modal-container";
                    }
                break;
            }
        };
    }
}

function shareFile(dir){
    const modal = document.querySelector('#modal-shareFile');

    if(modal.className == "container-fluid modal-container"){
        document.querySelector('#fileNameDownload').value = "";
        modal.className = "container-fluid modal-container-hidden";
    }else{
        document.querySelector('#fileNameDownload').value = dir;
        modal.className = "container-fluid modal-container";
    }
};