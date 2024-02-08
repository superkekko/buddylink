//message on extension install
chrome.runtime.onInstalled.addListener(function() {
	console.log("Buddylink Installed");
});

//listen call from popup.js
chrome.runtime.onMessage.addListener(function(message, sender, sendResponse) {
	let apiKey;
	let postUrl;

	console.log("URL to post: " + message.url);
	chrome.storage.local.get("apiKey", function(result) {
		apiKey = result.apiKey || "";
		console.log("API Key loaded in background script:", apiKey);
		if (message.action === "sendData") {
			chrome.storage.local.get("postUrl", function(result) {
				postUrl = result.postUrl || "";
				console.log("postUrl loaded in background script:", postUrl);
				sendData(postUrl, apiKey, message.url, message.list, message.tags);
			});
		} else if (message.action === "getData") {
			chrome.storage.local.get("getUrl", function(result) {
				getUrl = result.getUrl || "";
				console.log("getUrl loaded in background script:", getUrl);
				readData(getUrl, apiKey, message.url);
			});
		}
	});
});

//functions
function readData(postUrl, apiKey, sendUrl) {
	fetch(postUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + apiKey,
			},
			body: JSON.stringify({
				"url": sendUrl
			}),
		})
		.then(response => {
			if (!response.ok) {
				throw new Error(`HTTP error! Status: ${response.status}`);
			}
			return response.json();
		})
		.then(data => {
			console.log("Response:", data);
			chrome.runtime.sendMessage({
				action: "returnData",
				list: data.data.list,
				tags: data.data.tags,
				alltags: data.data.alltags,
				alllist: data.data.alllist
			});
		})
		.catch(error => {
			console.error('Error:', error.message);
			chrome.runtime.sendMessage({
				action: "dataNotReturn",
				data: error.message
			});
		});
}

function sendData(postUrl, apiKey, sendUrl, list, tags) {
	fetch(postUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + apiKey,
			},
			body: JSON.stringify({
				"url": sendUrl,
				"list": list,
				"tags": tags
			}),
		})
		.then(response => {
			if (!response.ok) {
				throw new Error(`HTTP error! Status: ${response.status}`);
			}
			return response.json();
		})
		.then(data => {
			console.log("Response:", data);
			chrome.runtime.sendMessage({
				action: "dataSent",
				data: ''
			});
		})
		.catch(error => {
			console.error('Error:', error.message);
			chrome.runtime.sendMessage({
				action: "dataNotSent",
				data: error.message
			});
		});
}