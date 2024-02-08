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
	
	$('#tags').select2({
		theme: "classic",
		placeholder: "Tags",
		tags: true,
		allowClear: true
	});
	
	$('#list').select2({
		theme: "classic",
		placeholder: "List",
		tags: true,
		allowClear: true
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
					list: $('#list').val(),
					tags: $('#tags').val().join()
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
			for (const [key, value] of Object.entries(message.alltags)) {
				if(message.tags.split(",").includes(value)){
					$('#tags').append(new Option(value, value, true, true)).trigger('change');
				}else{
					$('#tags').append(new Option(value, value, false, false)).trigger('change');	
				}
			}
			
			for (const [key, value] of Object.entries(message.alllist)) {
				if(value === message.list){
					$('#list').append(new Option(value, value, true, true)).trigger('change');
				}else{
					$('#list').append(new Option(value, value, false, false)).trigger('change');
				}
			}
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