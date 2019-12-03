var errorColor = '#ffb293';

function setError(range, error) {
    range.setNote(error);
    range.setBackground(errorColor);
}

function clearError(range) {
  range.setNote(null);
  range.setBackground(null);
}

function wifiStock(e) {
  // Set a comment on the edited cell to indicate when it was changed.
  var range = e.range;

  // Test if interesting column
  if (range.getColumn() != 7) return;
  
  // If emptied
  var ok, url;
  ok = e.value !== undefined;
  if (ok) url = e.value.trim();
  ok = ok && url !== '';
 
  var sheet = range.getSheet();
  var row = range.getRow();
 
  if (ok) {   
    // Fetch stuff
    Logger.log("https://zouppen.iki.fi/wifistock?url=" + url)
    var responseJson = UrlFetchApp.fetch("https://zouppen.iki.fi/wifistock?url=" + url);
    var info = JSON.parse(responseJson);
    if (info.error) {
      setError(range, info.error);
      //SpreadsheetApp.getUi().alert("Product data update failed. " + info.error);
      return;
    } else {
      // Remove notes if OK
      clearError(range)
    }
    
    // Fill in details
    sheet.getRange(row, 3).setValue(info.title);
    sheet.getRange(row, 4).setValue(info.price);
    var qty = sheet.getRange(row, 2);
    if (info.stock) {
      clearError(qty);
    } else {
      setError(qty, "Out of stock!");
    }
  } else {
    // Empty data cells and clear notes
    sheet.getRange(row, 3).setValue('');
    sheet.getRange(row, 4).setValue('');
    clearError(range);
    clearError(sheet.getRange(row, 2));
  }
}
