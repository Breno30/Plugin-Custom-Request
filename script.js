function createHTMLFromJSON(obj, keyList = []) {
    let html = '<div class="subnivel">';

    for (const key in obj) {      
        if (typeof obj[key] === 'object') {
            keyList.push(key);

            html += `
                <div class="subnivel__line">
                    <button data-json-path>${key}:</button>
                </div>
                <div class="subnivel">
                    ${createHTMLFromJSON(obj[key], keyList)}
                </div>
            `;
        } else {
            html += `
                <div class="subnivel__line">
                    <button onclick="setKeyPath('${keyList.join(',')},${key}')">${key}:</button><span>${obj[key]}</span>
                </div>
            `;
        }
    }
    html += '</div>';

    return html;
}

function setKeyPath(keyPath) {
    document.querySelector("#access_path").value = keyPath;
}

document.addEventListener('DOMContentLoaded', () => {

    let btn = document.getElementById('btn-send-request');
    let requestAnswer = document.getElementById('custom-request__answer');

    let initialData = document.getElementById('payload_response').value;

    if (initialData) {
        initialData = JSON.parse(initialData);
        requestAnswer.innerHTML = createHTMLFromJSON(initialData);
    }

    btn.addEventListener('click', (e) => {
        e.preventDefault();

        let url = document.getElementById('url').value;
        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                document.getElementById('payload_response').value = JSON.stringify(data);
                requestAnswer.innerHTML = createHTMLFromJSON(data);
            })
    })

})