var that = this;
var result = {

    componentInit: function() {

        // This.question should be provided to us here.
        // This.question.html (string) is the main source of data, presumably prepared by the renderer.
        // There are also other useful objects with question like infoHtml which is used by the
        // page to display the question state, but with which we need do nothing.
        // This code just prepares bits of this.question.html storing it in the question object ready for
        // passing to the template (varnum.html).
        // Note this is written in 'standard' javascript rather than ES6. Both work.

        if (!this.question) {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Create a temporary div to ease extraction of parts of the provided html.
        var div = document.createElement('div');
        div.innerHTML = this.question.html;

        // Replace Moodle's correct/incorrect classes, feedback and icons with mobile versions.
        that.CoreQuestionHelperProvider.replaceCorrectnessClasses(div);
        that.CoreQuestionHelperProvider.replaceFeedbackClasses(div);
        that.CoreQuestionHelperProvider.treatCorrectnessIcons(div);

        // Get useful parts of the provided question html data.
        var questiontext = div.querySelector('.qtext');
        var ablock = div.querySelector('.ablock');
        var ousupsub = div.querySelector('.answerwrap');

        // Add the useful parts back into the question object ready for rendering in the template.
        this.question.text = questiontext.innerHTML;
        // Without the question text there is no point in proceeding.
        if (typeof this.question.text === 'undefined') {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Display the answer block.
        if (ablock !== null) {
            this.question.ablock = ablock.innerHTML;
        }

        // The question allows ousupsub editor.
        // Since the app cannot process ousupsub editor yet we will prevent submitting the question.
        if (ousupsub !== null) {
            // If the question text (before it is rendered) has number of underscores to
            // adjust the width of ousupsub editor, replace the corresponding textara with blank.
            if (ablock !== null) {
                this.question.ablock = this.question.ablock.replace(ousupsub.innerHTML, '<Span class="varnumericset-ousupsub-box"></Span>');
            } else {
                this.question.text = this.question.text.replace(ousupsub.innerHTML, '<Span class="varnumericset-ousupsub-box"></Span>');
            }

            // This is used to provide error message (<ion-item text-wrap *ngIf="question.ousupsub" class="core-danger-item"> in varnumset.html. 
            this.question.ousupsub = ousupsub;
        }
        return true;
    },

    /**
     * Check if a question can be submitted.
     * If a question cannot be submitted it should return a message explaining why (translated or not).
     *
     * @param {any} question The question.
     * @return {string} Prevent submit message. Undefined or empty if can be submitted.
     */
    getPreventSubmitMessage: function (question) {
        var div = document.createElement('div');
        div.innerHTML = question.html;
        var ousupsub = div.querySelector('.answerwrap');

        if (ousupsub !== null) {
            return 'plugin.qtype_varnumericset.err_ousupsubnotsupportedonmobile';
        }
    }
};


// This next line is required as is (because of an eval step that puts this result object into the global scope).
result;
