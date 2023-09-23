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
            let finalKeyList = `${keyList.join(',')},${key}`;

            html += `
                <div class="subnivel__line">
                    <button data-key-list='${finalKeyList}' onclick="setKeyPath('${finalKeyList}')">${key}:</button><span>${obj[key]}</span>
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

    let btnSendRequest = document.getElementById('btn-send-request');
    let requestAnswer = document.getElementById('custom-request__answer');
    let initialData = document.getElementById('payload_response');
    let accessPath = document.getElementById('access_path');

    let initialDataValue = initialData.value;
    let accessPathValue = accessPath.value;

    // Create Initial Json Structure
    if (initialDataValue) {
        initialDataValue = JSON.parse(initialDataValue);
        requestAnswer.innerHTML = createHTMLFromJSON(initialDataValue);
    }

    // Set Initial Value
    if (accessPathValue) {
        let buttonActive = document.querySelector(`[data-key-list="${accessPathValue}"]`);
        if (buttonActive) buttonActive.classList.add('active');
    }
    

    btnSendRequest.addEventListener('click', (e) => {
        e.preventDefault();

        let url = document.getElementById('url').value;
        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                initialDataValue = JSON.stringify(data);
                requestAnswer.innerHTML = createHTMLFromJSON(data);
            })
    })

})