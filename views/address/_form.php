<div class="row">
    <div class="col-xs-12">
        <?= $form->field($model, 'street1')->textInput(['maxlength' => 255]) ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <?= $form->field($model, 'street2')->textInput(['maxlength' => 255]) ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-6">
        <?= $form->field($model, 'city')->textInput(['maxlength' => 255]) ?>
    </div>
    <div class="col-xs-2">
        <?= $form->field($model, 'state')->dropDownList(AddressState::getStateList(),['prompt' => '--']); ?>
    </div>
    <div class="col-xs-4">
        <?= $form->field($model, 'zip')->textInput(['maxlength' => 20]) ?>
    </div>
</div>