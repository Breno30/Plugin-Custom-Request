function createHTMLFromJSON(obj, keyList = []) {
    let html = '<div class="subnivel">';

    for (const key in obj) {      
        if (typeof obj[key] === 'object') {
            keyList.push(key);

            html += `
                <div class="subnivel__line">${key}</div>
                <div class="subnivel">
                    ${createHTMLFromJSON(obj[key], keyList)}
                </div>
            `;

            keyList.pop(); // Remove the last key when moving back up the tree.
        } else {
            let finalKeyList = `${keyList.join(',')},${key}`;

            html += `
                <div class="subnivel__line">
                    <button data-key-list='${finalKeyList}' onclick="setKeyPath('${finalKeyList}')">${key}</button>:<span>${obj[key]}</span>
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

function validateCustomFields(event) {

    const shortcode = document.getElementById("shortcode");
    if (!shortcode || shortcode.value.trim() === "") {
        event.preventDefault(); 
        alert("Please enter a valid shortcode.");
        return;
    }
    
    const url = document.getElementById("url");
    if (!url || url.value.trim() === "") {
        event.preventDefault();
        alert("Please enter a valid URL.");
        return;
    }

    const accessPath = document.getElementById("access_path");
    if (!accessPath || accessPath.value.trim() === "") {
        event.preventDefault();
        handleSendRequest();
        alert("Please select one of the answers listed.");
        return;
    }
}

function handleSendRequest(event = null) {
    if (event) event.preventDefault();

    const url = document.getElementById('url').value;
    const initialData = document.getElementById('payload_response');
    const requestAnswer = document.getElementById('custom-request__answer');

    fetch(url)
        .then(response => response.json())
        .then(data => {
            initialData.value = JSON.stringify(data);
            requestAnswer.innerHTML = createHTMLFromJSON(data);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const requestAnswer = document.getElementById('custom-request__answer');
    const initialData = document.getElementById('payload_response');
    const accessPath = document.getElementById('access_path');
    const initialDataValue = initialData.value;
    const accessPathValue = accessPath.value;

    if (initialDataValue) {
        requestAnswer.innerHTML = createHTMLFromJSON(JSON.parse(initialDataValue));
    }

    if (accessPathValue) {
        const buttonActive = document.querySelector(`[data-key-list="${accessPathValue}"]`);
        if (buttonActive) buttonActive.classList.add('active');
    }

    document.getElementById('btn-send-request').addEventListener('click', handleSendRequest);

    ['publish', 'save-post'].forEach(id => {
        const button = document.getElementById(id);
        if (button) button.addEventListener('click', validateCustomFields);
    });
});
