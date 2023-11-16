document.addEventListener("DOMContentLoaded", function() {
	//read data from server
    chrome.tabs.query({
        active: true,
        currentWindow: true
    }, function(tabs) {
        var currentTab = tabs[0];
        if (currentTab) {
            showLoader();
            chrome.runtime.sendMessage({
                action: "getData",
                url: currentTab.url
            });
        }
    });
	
	//send data to serrver
    document.getElementById("sendRequest").addEventListener("click", function() {
        chrome.tabs.query({
            active: true,
            currentWindow: true
        }, function(tabs) {
            var currentTab = tabs[0];
            if (currentTab) {
                showLoader();
                chrome.runtime.sendMessage({
                    action: "sendData",
                    url: currentTab.url,
                    list: document.getElementById("list").value,
                    tags: document.getElementById("tags").value
                });
            }
        });
    });
	
	//listen call from backround.js
    chrome.runtime.onMessage.addListener(function(message, sender, sendResponse) {
        hideLoader();
        if (message.action === "dataSent") {
            statusMessage.innerText = 'Data sent';
        } else if (message.action === "dataNotSent") {
            statusMessage.innerText = 'Data not sent';
        } else if (message.action === "returnData") {
            document.getElementById("list").value = message.list;
            document.getElementById("tags").value = message.tags;
        }
    });
	
	//loader function
    function showLoader() {
        loader.classList.remove("hidden");
    }

    function hideLoader() {
        loader.classList.add("hidden");
    }
});