<style>
    #uni_modal .modal-footer {
        display: none !important;
    }

    .container-fluid {
        background-color: #f4f6f9;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .form-group label {
        font-weight: 600;
        color: #2c3e50;
    }

    .form-control {
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 10px;
        font-size: 16px;
        text-transform: uppercase; /* Capital letters */
    }

    .form-control:disabled {
        background-color: #e9ecef;
        color: #495057;
    }

    .btn-primary {
        background-color: #007bff;
        border: none;
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-dark {
        background-color: #343a40;
        border: none;
        padding: 8px 16px;
        font-size: 14px;
    }

    .form-section-title {
        font-size: 1.25rem;
        margin-bottom: 10px;
        font-weight: bold;
        color: #1e2a38;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 form-group">
            <label for="amount" class="form-section-title">Payable Amount</label>
            <input type="text" id="amount" class="form-control text-end" value="<?php echo $_GET['amount'] ?>" disabled>
        </div>
        <div class="col-md-6 form-group">
            <label for="tender" class="form-section-title">Tendered Amount</label>
            <input type="number" step="any" id="tender" class="form-control text-end" value="0">
        </div>
        <div class="col-md-12 form-group">
            <label for="change" class="form-section-title">Change</label>
            <input type="text" id="change" class="form-control text-end" value="0" disabled>
        </div>
        <div class="col-md-12 form-group">
            <label for="patient_name_input" class="form-section-title">Patient Name</label>
            <input type="text" id="patient_name_input" class="form-control text-end">
        </div>
        <div class="col-md-12 form-group">
            <label for="hospital_input" class="form-section-title">Hospital</label>
            <input type="text" id="hospital_input" class="form-control text-end">
        </div>
        <div class="col-md-4 form-group">
            <label for="surgeon_input" class="form-section-title">Surgeon</label>
            <input type="text" id="surgeon_input" class="form-control text-end">
        </div>
        <div class="col-md-4 form-group">
            <label for="technician_input" class="form-section-title">Technician</label>
            <input type="text" id="technician_input" class="form-control text-end">
        </div>
        <div class="col-md-4 form-group">
            <label for="sales_invoice_input" class="form-section-title">Sales Invoice</label>
            <input type="text" id="sales_invoice_input" class="form-control text-end">
        </div>
        <div class="col-md-12 form-group">
            <label for="remarks_input" class="form-section-title">Remarks</label>
            <textarea id="remarks_input" class="form-control" rows="2" style="text-transform: uppercase;"></textarea>
        </div>
        <div class="col-md-12 form-group">
            <label for="sets_input" class="form-section-title">ORDER</label>
            <textarea id="sets_input" class="form-control" rows="2" style="text-transform: uppercase;"></textarea>
        </div>
        <div class="w-100 d-flex justify-content-end mt-3">
            <button class="btn btn-primary me-2 rounded-1" type="button" id="save_trans">Save</button>
            <button class="btn btn-dark rounded-1" type="button" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('#uni_modal').on('shown.bs.modal', function () {
            if ($('#tender').length > 0)
                $('#tender').trigger('focus').select();
        });

        $('#tender').on('keydown', function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#save_trans').trigger('click');
            }
        });

        $('#tender').on('input', function () {
            let tender = parseFloat($(this).val()) || 0;
            let amount = parseFloat($('#amount').val().replace(/,/g, '')) || 0;
            $('[name="tendered_amount"]').val(tender);
            let change = tender - amount;
            $('#change').val(change.toLocaleString('en-US', { minimumFractionDigits: 2 }));
            $('[name="change"]').val(change);
        });

        $('#tender').focusout(function () {
            if ($(this).val() <= 0) $(this).val(0);
        });

        $('#save_trans').click(function () {
            $('#change').removeClass('border-danger');
            if (parseFloat($('[name="change"]').val()) < 0) {
                $('#change').addClass('border-danger');
            } else if (parseFloat($('#tender').val()) <= 0) {
                $('#tender').trigger('focus');
            } else {
                const addHiddenField = (name, val) => {
                    if ($('#transaction-form [name="' + name + '"]').length === 0) {
                        $('<input>').attr({ type: 'hidden', name, value: val.toUpperCase() }).appendTo('#transaction-form');
                    } else {
                        $('#transaction-form [name="' + name + '"]').val(val.toUpperCase());
                    }
                };

                addHiddenField('patient_name', $('#patient_name_input').val());
                addHiddenField('surgeon', $('#surgeon_input').val());
                addHiddenField('hospital', $('#hospital_input').val());
                addHiddenField('technician', $('#technician_input').val());
                addHiddenField('sales_invoice', $('#sales_invoice_input').val());
                addHiddenField('remarks', $('#remarks_input').val());
                addHiddenField('sets', $('#sets_input').val());

                $('#uni_modal').modal('hide');
                $('#transaction-form').submit();
            }
        });
    });
</script>
