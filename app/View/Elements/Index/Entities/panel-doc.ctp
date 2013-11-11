<div class="row-fluid">
    <div class="span12">
        <div class="box box-bordered">
            <div class="box-content nopadding">
                <!-- Documento -->
                <?php echo $this->AppForm->create($modelClass, array('defaultSize' => 'input-xlarge', 'classForm' => 'form-horizontal form-bordered'))?>
                    <?php echo $this->form->hidden('q', array('value' => $requestHandler));?>
                    <div class="control-group address-fields">
                        <label class="control-label" style="padding:10px 0 10px 30px; width:auto;"><i class="glyphicon-vcard"></i>&nbsp;<?php echo __('Search by doc')?></label>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="textfield"><?php echo __('Type Document')?></label>
                        <div class="controls">
                            <div class="input-append input-prepend">
                                <span class="add-on"><i class="icon-search"></i></span>
                                <?php $doc = isset($this->params['named']['doc']) && !empty($this->params['named']['doc'])?$this->params['named']['doc']:''?>
                                <?php echo $this->AppForm->input('doc', array('template' => 'form-input-clean', 'value' => $doc, 'placeholder' => __('type document')))?>
                                <button type="submit" class="btn"><?php echo __('Search')?></button>
                            </div>
                        </div>
                    </div>
                <?php echo $this->AppForm->end()?>
            </div>
        </div>
    </div>
</div>