import DateTimePicker from "@typo3/backend/date-time-picker.js";
import DocumentService from "@typo3/core/document-service.js";
import "@typo3/backend/input/clearable.js";
class GdprDatePickerModule {
    constructor() {
        this.clearableElements = null, this.dateTimePickerElements = null, DocumentService.ready().then((() => {
            this.clearableElements = document.querySelectorAll(".t3js-clearable"), this.dateTimePickerElements = document.querySelectorAll(".t3js-datetimepicker"), this.initializeClearableElements(), this.initializeDateTimePickerElements()
        }))
    }

    initializeClearableElements() {
        this.clearableElements.forEach((e => e.clearable()))
    }

    initializeDateTimePickerElements() {
        this.dateTimePickerElements.forEach((e => DateTimePicker.initialize(e)))
    }
}
export default new GdprDatePickerModule;
