function isSelectionMade(select) {
    const click = select.dataset.click === "true";
    const key = select.dataset.key === "true";
    const change = select.value !== select.dataset.initialValue;

    return (click || key) && change;
}
  
  //Event Listeners:

//Check Mouse Click
document.addEventListener("click", function(e) {
    e.target.dataset.click = "true";
    e.target.dataset.change = "false";
});

//Check Keyboard
document.addEventListener("keydown", function(e) {
    e.target.dataset.key = "true";
    e.target.dataset.change = "false";
});

document.addEventListener("keyup", function (e) {
    e.target.dataset.key = "false";
    
});

//Get the initial value
document.addEventListener("focus", function(e) {
    e.target.dataset.initialValue = e.target.value;
}, true);