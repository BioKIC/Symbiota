function sendRequest(url, method, path, currentEnabledStylesheet) {
  return new Promise((resolve, reject) => {
    const xmlRequest = new XMLHttpRequest();
    xmlRequest.open(method, url);
    xmlRequest.setRequestHeader("Content-Type", "application/json");
    xmlRequest.onreadystatechange = () => {
      if (xmlRequest.readyState === 4) {
        if (xmlRequest.status === 200) {
          resolve(xmlRequest.responseText);
        } else {
          reject(xmlRequest.statusText);
        }
      }
    };
    xmlRequest.send(
      JSON.stringify({
        path: path,
        currentEnabledStylesheet: currentEnabledStylesheet,
      })
    );
  });
}

async function toggleAccessibilityStyles(
  pathToToggleStyles,
  cssPath,
  viewCondensed,
  viewAccessible
) {
  try {
    const currentEnabledStylesheet = getEnabledLink().getAttribute("href");
    const response = await sendRequest(
      pathToToggleStyles + "/toggle-styles.php",
      "POST",
      cssPath,
      currentEnabledStylesheet
    );
    await handleResponse(response, viewCondensed, viewAccessible);
  } catch (error) {
    console.log(error);
  }
}

async function handleResponse(
  stylesheetReferencedInSession,
  viewCondensed,
  viewAccessible
) {
  let links = document.querySelectorAll("[data-accessibility-link]");

  for (let i = 0; i < links.length; i++) {
    if (links[i].getAttribute("href") === stylesheetReferencedInSession) {
      links[i].disabled = false;
    } else {
      links[i].disabled = true;
    }
  }
  updateButtonTextBasedOnEnabledStylesheet(viewCondensed, viewAccessible);
}

function updateButtonTextBasedOnEnabledStylesheet(
  viewCondensed,
  viewAccessible
) {
  let enabledLink = getEnabledLink();
  const isAccessibleLinkEnabled =
    enabledLink
      ?.getAttribute("href")
      ?.indexOf("/symbiota/accessibility-compliant.css") > 0;
  let buttons = document.querySelectorAll("[data-accessibility]");
  const newText = isAccessibleLinkEnabled ? viewCondensed : viewAccessible;
  for (let j = 0; j < buttons.length; j++) {
    buttons[j].textContent = newText;
  }
}

function getEnabledLink() {
  let links = document.querySelectorAll("[data-accessibility-link]");
  let enabledLink;
  for (let i = 0; i < links.length; i++) {
    if (!links[i].disabled) {
      enabledLink = links[i];
    }
  }
  return enabledLink;
}
