'use strict'

document.addEventListener("DOMContentLoaded", async () => {

	console.log('Epsilon SAP Sync Admin JS Loaded');

	// console.log(ajax_object);

	var syncButton = document.getElementById('epsilon-sap-sync__manual-sync');

	var loginButton = document.getElementById('epsilon-sap-sync__login');

	var loading = document.getElementById('sync-loading-status');

	var resultText = document.getElementById('sync-result__text');

	var logOutButton = document.getElementById('epsilon-sap-sync__logout');

	syncButton.addEventListener('click', async function (e) {

		e.preventDefault();

		//show loading

		loading.style.display = 'block';

		resultText.innerHTML = 'This may take a while...<br>';

		var formData = new FormData();

		var target_btn = e.target;

		//disable button

		target_btn.setAttribute('disabled', 'disabled');

		formData.append('action', 'sync_sap_full_in_chunks');

		fetch(ajax_object.ajax_url, {
			method: 'POST',
			body: formData,
		}).then(response => {

			if (response.status !== 200) {

				throw new Error('Looks like there was a problem. Status Code: ' + response.status);

			}

			return response.json();

		}).then(data => {

			resultText.innerHTML = data.data;

			//enable button

			target_btn.removeAttribute('disabled');


		}).catch(error => {

			resultText.innerHTML = error;

		}).finally(() => {

			//hide loading
			loading.style.display = 'none';

		});

	});

	//add event listener to login button

	loginButton.addEventListener('click', async function (e) {

		loading.style.display = 'block';

		resultText.innerHTML = '';

		e.preventDefault();

		//create a fetch request to the login endpoint

		var formData = new FormData();

		formData.append('action', 'manual_login_to_sap');

		await fetch(ajax_object.ajax_url, {

			method: 'POST',

			body: formData,

		}).then(response => {

			if (response.status !== 200) {

				throw new Error('Looks like there was a problem. Status Code: ' + response.status);

			}

			return response.json();

		}).then(data => {

			//if data is true, enable button

			if (data.data.status === true) {

				syncButton.removeAttribute('disabled');

				loginButton.setAttribute('disabled', 'disabled');

				//set the array of cookies in the response to the document.cookie

				//iterate through the data.data.cookies object

				for (let key in data.data.cookies) {

					let cookie = data.data.cookies[key];

					let cookieString = cookie.name + "=" + cookie.value;

					if (cookie.expires) {
						let expires = new Date(cookie.expires).toUTCString();
						cookieString += "; expires=" + expires;
					}

					if (cookie.path) {
						cookieString += "; path=/";
					}

					if (cookie.domain) {
						cookieString += "; domain=" + window.location.hostname;
					}

					if (cookie.port) {
						cookieString += "; port=" + cookie.port;
					}

					if (cookie.host_only) {
						cookieString += "; hostOnly";
					}

					document.cookie = cookieString;

				} //end for loop

				//remove message

				resultText.innerHTML = '';



			} else {


				throw new Error('Login Failed');

			}

			//disable login button



		}).catch(error => {

			syncButton.setAttribute('disabled', 'disabled');

			resultText.innerHTML = error.toString();

		}).finally(() => {

			loading.style.display = 'none';

		});

	});

	logOutButton.addEventListener('click', async function (e) {

		//clear the cookies

		document.cookie = "B1SESSION=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

		document.cookie = "ROUTEID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

		//disable sync button

		syncButton.setAttribute('disabled', 'disabled');

		//enable login button

		loginButton.removeAttribute('disabled');


	});

	var loginCookiesFound = document.cookie.indexOf('B1SESSION') != -1 && document.cookie.indexOf('ROUTEID') != -1 ? true : false;

	var logginInStatus = await isLoogedIn();

	//if user is logged in and cookies are found, enable sync button
	if (logginInStatus && loginCookiesFound === true) {

		syncButton.removeAttribute('disabled');

		loginButton.setAttribute('disabled', 'disabled');

	} else {

		syncButton.setAttribute('disabled', 'disabled');

		loginButton.removeAttribute('disabled');

		//remove the cookies

		document.cookie = "B1SESSION=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

		document.cookie = "ROUTEID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";


	}

});

//check if user is logged in


const fetchLoginResponse = async (a) => {

	var formData = new FormData();

	formData.append('action', 'check_login');

	try {
		const response = await fetch(ajax_object.ajax_url, {
			method: 'POST',
			body: formData,
		});

		if (response.status !== 200) {
			throw new Error('Looks like there was a problem. Status Code: ' + response.status);
		}

		return await response.json();

	} catch (error) {
		console.error(error);
	}
};

/**
 * 
 * @returns	{boolean}	true if user is logged in, false if not
 * 
 * @since	1.0.0
 * 
 */

const checkLogin = async () => {

	const theresponse = await fetchLoginResponse();

	if (theresponse.success === true)

		return theresponse.data;

	else

		return false;
};

/**
 * 
 * @returns	{boolean}	true if user is logged in, false if not
 * 
 * @since	1.0.0
 * 
 * */

const isLoogedIn = async () => {

	const isLoogedIn = await checkLogin();

	return isLoogedIn;
};
