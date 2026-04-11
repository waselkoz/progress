window.showToast = function(message) {
    let toast = document.getElementById('sys-toast');
    if(!toast) {
        toast = document.createElement('div');
        toast.id = 'sys-toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.innerText = message;
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 3000);
};

window.confirmAction = function(event, message, actionUrl) {
    event.preventDefault();
    if(confirm(message)) {
        window.location.href = actionUrl;
    }
};

window.filterTable = function(inputId, tableId, columnIndex = 0) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    
    if (!table || !input) return;
    
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName("td")[columnIndex];
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().trim().startsWith(filter)) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
};
