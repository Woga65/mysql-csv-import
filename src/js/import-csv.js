/* backend end points */
const endPoints = {
    importCsv: 'includes/import-csv.inc.php',
    getCsvFiles: 'includes/get-csv-files.inc.php',
}

/* the forms array */
const forms = [
    {
        endPoint: endPoints.importCsv,
        form: document.getElementById('csv-form'),
        submit: document.querySelector('.csv-form .submit-button'),
        formFields: document.querySelectorAll('.csv-form .form-field'),
        isDirty: new Array(document.querySelectorAll('.csv-form .form-field').length).fill(false),
        defaultErrorMessages: [],
        defaultValues: ['', ';', '"', '', '', '', '250', ''],
        formChange: csvFormChanged,
        dataSent: csvSuccess,
        errorResponseCallback: csvError,
    },
];

/* progress bar element */
const importProgress = document.querySelector('.csv-form .progress-bar');

/* special handling for the input fields below*/
const inChunksElement = document.getElementById('inchunks');
const chunkSizeElement = document.getElementById('chunksize');


init();


/* initialize the form submitter(s) */
async function init() {
    getCsvFileNames();
    initUserInterface();        // generic initialization
    addCheckboxListener();      // disable chunkSize input if inChunks is not checked
}


/* initialize forms and ui related stuff */ 
function initUserInterface() {
    forms.forEach((form, index) => {
        form.formFields.forEach((ff, j) => {
            form.defaultErrorMessages.push(ff.parentElement.lastElementChild.textContent.replace(/[\n\r]/g, '')); //save default hints
            ff.value = form.defaultValues[j] ? form.defaultValues[j] : ff.value;           //get default values
        });
        addFormFieldListeners(form);                                                       //form fields check valid data  
        form.submit.addEventListener('click', submitPreflightListener.bind(null, form));   //submit button clicked, check valid form data
        form.form.addEventListener('submit', submitListener.bind(null, form, index));      //on submit send form data to the end point
        form.form.addEventListener('change', formChangedListener.bind(null, form, index)); //on form data change
    });
    setTranslucentBackgroundColor();
    setTimeout(() => document.querySelector('.fade-in').style.opacity = '1', 125);         //let the component's body fade in
}


/* get the primary background color, add transparency  
 * and store it in a css variable  */
function setTranslucentBackgroundColor() {
    const r = document.querySelector(':root');
    const cssVar = getComputedStyle(r).getPropertyValue('--primary-bgr').trim();
    if (cssVar) {
        r.style.setProperty('--primary-bgr-translucent', cssVar.startsWith('#') ? cssVar + '80' : cssVar);
    }
}


/* event listeners to check whether invalid 
   data has been entered into a form field */
function addFormFieldListeners(form) {
    form.formFields.forEach((ff, i) => {
        ['blur', 'keyup'].forEach(ev => ff.addEventListener(ev, e => {
            ff.parentElement.lastElementChild.textContent = form.defaultErrorMessages[i];
            if (e.type == 'blur') {
                form.isDirty[i] = ff.value ? true : false;
            }
            !ff.value || !form.isDirty[i] ? ff.classList.toggle('invalid', false) : ff.classList.toggle('invalid', !ff.checkValidity());
        }));
    });
}


/* event listener, on form data change */
function formChangedListener(form, index, e) {
    if (form.formChange) {
        forms[index].formChange(form, index, e);
    }
}


/* event listener, on submit button clicked, check
   if all required data has been entered correctly */
function submitPreflightListener(form, e) {
    let invalidField = null;
    form.formFields.forEach(ff => {
        invalidField = ff.required ? (ff.checkValidity() ? invalidField : invalidField ? invalidField : ff) : invalidField;
        ff.classList.toggle('invalid', !ff.checkValidity());
    });
    if (invalidField) {
        e.preventDefault();
        invalidField.focus();
    }
}


/* event listener, on submit send form data to the endpoint*/
function submitListener(form, index, e) {
    e.preventDefault();
    const formData = new FormData(form.form);
    const formDataObject = Object.fromEntries(formData);
    form.submit.disabled = true;
    submitRequest(form.endPoint, formDataObject)
        .then(async result => {
            if (result.ok) {
                await form.dataSent(index, result, formDataObject);
            } else {
                reportInvalidFormData(index, result);
                form.errorResponseCallback(result);
                console.log('result: ', result);
            }
            form.submit.disabled = false;
        });
}


/* send request to the endpoint */
async function submitRequest(endPoint, dataObject) {
    try {
        const response = await fetch(endPoint, {
            method: 'POST',
            body: JSON.stringify(dataObject),
            headers: { 'Content-Type': 'application/json' }
        });
        return await response.json();
    } catch (err) {
        console.error(err);
        return { err: err, ok: false, data: {} };
    }
}


/* check which fields were invalid 
   and focus the first invalid field */
function reportInvalidFormData(index, result) {
    if (!result.fields) {
        clearFormData(index);
    } else {
        let invalidField = null;
        forms[index].formFields.forEach(ff => invalidField = checkForInvalid(ff, result, invalidField));
        if (invalidField) invalidField.focus();
    }
}


/* set the hint to the status message reported by 
   the backend, determine which field to focus */
function checkForInvalid(ff, result, invalidField) {
    if (result.fields.includes(ff.name)) {
        ff.classList.toggle('invalid', true);
        ff.parentElement.lastElementChild.textContent = result.err;
        invalidField = invalidField ? invalidField : ff;
    }
    return invalidField;
}


/* initialize form data */
function clearFormData(index) {
    forms[index].formFields.forEach((ff, j) => {
        ff.value = forms[index].defaultValues[j] ? forms[index].defaultValues[j] : '';
        forms[index].isDirty[j] = false;
        ff.parentElement.lastElementChild.textContent = forms[index].defaultErrorMessages[j];
        ff.classList.toggle('invalid', false);
    });
}




/* actions to perform after the form
   endpoint has reported an error */
function csvError(result) {
    updateInputFileState();
    hideFieldAssignments();
}


/* actions to perform after the form
   has been successfully submitted */
async function csvSuccess(index, result, formData) {
    if ('data' in result && 'source' in result.data && 'target' in result.data) { 
        renderFieldAssignments(result);
    } else {
        if ('inchunks' in formData) {
            await processDataInChunks(result.data.chunkSize, index, result, formData);
        }
        clearFormData(index);
        updateInputFileState();
        hideFieldAssignments();
    }
}


/* process the import of the csv file in chunks */
async function processDataInChunks(chunkSize, index, result, formData) {
    let csvRowsRead = result.data.rowsRead;
    formData.offset = result.data.csvOffset;
    updateProgressBar(Math.floor((result.data.csvOffset / result.data.fileSize) * 100));
    while (csvRowsRead >= chunkSize) {
        csvRowsRead = await processChunk(forms[index], formData);
    }
    updateProgressBar(0);
}


/* process a chunk of csv rows */
async function processChunk(form, formData) {
    let csvRowsRead;
    await submitRequest(form.endPoint, formData)
    .then(resp => {
        csvRowsRead = resp.data.rowsRead;
        formData.offset = resp.data.csvOffset;
        updateProgressBar(Math.floor((resp.data.csvOffset / resp.data.fileSize) * 100));
    });
    return csvRowsRead;
}


/* render field assignment selection
   and input field for confirmation */
function renderFieldAssignments(result) {
    const assignments = document.getElementById('assignments');
    const confirmation = document.getElementById('confirmation');
    const submitButtonText = document.querySelector('#csv-submit .button-text');
    assignments.innerHTML = hiddenOffsetTemplate();
    result.data.target.forEach(target => assignments.appendChild(csvFieldTemplate(target, result.data.source)));
    confirmation.style.display = 'block';
    confirmation.style.opacity = 1;
    submitButtonText.innerText = 'Start Import / Get CSV Struct';
    assignments.querySelector('select').focus();
    assignments.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });
}


/* hide field assignment selection
   and input field for confirmation */
function hideFieldAssignments() {
    const assignments = document.getElementById('assignments');
    const confirmation = document.getElementById('confirmation');
    const submitButtonText = document.querySelector('#csv-submit .button-text');
    confirmation.style.opacity = 0;
    confirmation.style.display = '';
    assignments.innerHTML = '';
    submitButtonText.innerText = 'Get CSV Structure';
    document.body.scrollIntoView({ block: 'start', inline: 'start', behavior: 'smooth' });
}


/* listen for changes on inChunks checkbox */
function addCheckboxListener() {
    setStateOfChunkSizeInput();
    inChunksElement.addEventListener('change', e => setStateOfChunkSizeInput());
}


/* if inChunks checkbox is checked, enable  
 * chunkSize input else disable chunkSize input */
function setStateOfChunkSizeInput() {
    (inChunksElement.checked === false)
        ? chunkSizeElement.disabled =true
        : chunkSizeElement.disabled =false;
}


/* update the progress bar percentage */
function updateProgressBar(percent) {
    importProgress.style.width = percent + "%";
}


/* dismiss any field assignments,
 * if the input file has changed. */
function csvFormChanged(form, index, e) {
    if (['inputfile', 'separator', 'enclosure'].includes(e.target.id)) {
        hideFieldAssignments();
        updateInputFileState();
        document.getElementById('start').value = '';
    }
}


/* Update the state of the input file options. */
function updateInputFileState() {
    const inputFile = document.getElementById('inputfile');
    inputFile.firstElementChild.hidden = inputFile.value ? false : true;
    inputFile.firstElementChild.disabled = inputFile.value ? false : true;
}


/* retrieve and render a list of the CSV files
 * that were uploaded to the data folder */
async function getCsvFileNames() {
    return await submitRequest(endPoints.getCsvFiles, {})
        .then(result => {
            result.data = result.ok ? result.data : { files: [] }; 
            renderInputFileOptions(result.data.files);
        });
}


/* render the input file's option elements */
function renderInputFileOptions(files) {
    const inputFile = document.getElementById('inputfile');
    files.forEach(file => {
        const option = document.createElement('option');
        option.value = option.textContent = file.name;
        inputFile.appendChild(option);
    });
}


/* HTML templates */

function csvFieldTemplate(dbCol, csvColumns) {
    const div = document.createElement('div');
    div.classList.add('field-container', 'inline');
    div.appendChild(labelTemplate(dbCol));
    div.appendChild(selectTemplate(dbCol, csvColumns));
    return div;
}


function labelTemplate(dbCol) {
    const label = document.createElement('label');
    label.htmlFor = dbCol;
    label.textContent = dbCol + ': ';
    return label;
}


function selectTemplate(dbCol, csvColumns) {
    const select = document.createElement('select');
    select.classList.add('form-field');
    select.name = select.id = dbCol;
    select.innerHTML = '<option value="">none</option>';
    csvColumns.forEach(csvCol => {
        const option = document.createElement('option');
        option.value = option.textContent = csvCol;
        select.appendChild(option);
    });
    return select;
}


function hiddenOffsetTemplate() {
    return `
        <input class="form-field" type="hidden" name="offset" id="offset" value="0">
    `.trim();
}


/*
    // this would have been much shorter
    // but also vulnerable to xss attacks

    return `
        <div class="field-container inline">
            <label for='${dbCol}' id='${dbCol}'>${dbCol}: </label>
            <select class='form-field' name='${dbCol}' id='${dbCol}'>
                <option value="">none</option>
                ${csvColumns.map(csvCol => '<option value="' + csvCol + '">' + csvCol + '</option>')}
            </select>
        </div>
    ` . trim();
*/
