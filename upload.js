document.getElementById('imageUpload').addEventListener('change', function (e) {
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';
    [...e.target.files].forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function (event) {
            const div = document.createElement('div');
            div.className = 'piu-preview';
            div.innerHTML = `
                <img src="${event.target.result}" alt="" />
                <input type="text" name="description_${index}" placeholder="Description (e.g. kitchen, view)" required>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

document.getElementById('piu-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const projectName = form.projectName.value;
    const images = form.imageUpload.files;
    const descriptions = [];
    document.querySelectorAll('#previewContainer input').forEach(input => {
        descriptions.push(input.value);
    });

    const data = new FormData();
    data.append('action', 'piu_handle_upload');
    data.append('security', piu_ajax.nonce);
    data.append('projectName', projectName);
    for (let i = 0; i < images.length; i++) {
        data.append('images[]', images[i]);
    }
    descriptions.forEach(desc => data.append('descriptions[]', desc));

    fetch(piu_ajax.ajax_url, {
        method: 'POST',
        body: data
    }).then(res => res.json()).then(res => {
        if (res.success) {
            document.getElementById('uploadResult').innerHTML = '<p><strong>Upload Successful!</strong></p>';
        } else {
            document.getElementById('uploadResult').innerHTML = '<p><strong>Error:</strong> ' + res.data + '</p>';
        }
    });
});
