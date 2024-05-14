const main = document.getElementById('innertext');
let div = document.createElement('div');
div.id = 'alert-msgs';
div.classList.add('alerts');
main.appendChild(div);

function handleAlerts(alerts,id,limitMessage) {
	if(limitMessage){
		var c = decodeURIComponent(document.cookie);
		if(c.indexOf("alertCnt="+id) > -1) return false;
	}
	let alertDiv = document.getElementById('alert-msgs');
	console.log(alertDiv);
	alertDiv.innerHTML = '';
	alerts.map((alert) => {
		alertDiv.classList.remove('visually-hidden');
		let alertP = document.createElement('p');
		alertP.classList.add('alert');
		alertP.innerHTML = alert.alertMsg + ' Click to dismiss.';
		alertP.onclick = function () {
			if(limitMessage) document.cookie = "alertCnt="+id;
			else document.cookie = "alertCnt=; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
			alertP.remove();
		};
		alertDiv.appendChild(alertP);
	});
}
