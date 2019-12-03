var errorColor = '#ffb293';

var cols = {
  qty: 2,
  code: 3,
  title: 4,
  price: 5,
  url: 8,
};

function setError(range, error) {
    range.setNote(error);
    range.setBackground(errorColor);
}

function clearError(range) {
  range.setNote(null);
  range.setBackground(null);
}

// Trigger update manually on given line
function updateLine() {
  var range = SpreadsheetApp.getActiveRange();
  var sheet = range.getSheet();
  var start = range.getRow();
  var end = start + range.getNumRows();
  for (var i = start; i < end; i++) {
    var cell = sheet.getRange(i, cols.url);
    wifiStock({
      value: cell.getValue(),
      range: cell
    });
  }
}

// Action run on edit on any cell.
function wifiStock(e) {
  // Set a comment on the edited cell to indicate when it was changed.
  var range = e.range;

  // Test if interesting column and not header row
  if (range.getColumn() != cols.url) return;
  if (range.getRow() < 3) return;
  
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
    sheet.getRange(row, cols.code).setValue(info.code);
    sheet.getRange(row, cols.title).setValue(info.title);
    sheet.getRange(row, cols.price).setValue(info.price);
    var qty = sheet.getRange(row, cols.qty);
    if (info.stock) {
      clearError(qty);
    } else {
      setError(qty, "Out of stock!");
    }
  } else {
    // Empty data cells and clear notes
    sheet.getRange(row, cols.code).setValue('');
    sheet.getRange(row, cols.title).setValue('');
    sheet.getRange(row, cols.price).setValue('');
    clearError(range);
    clearError(sheet.getRange(row, cols.qty));
  }
}
