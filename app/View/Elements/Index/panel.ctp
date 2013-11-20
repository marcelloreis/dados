<div class="row-fluid">
    <div class="span12">
        <div class="box box-bordered">
            <div class="box-content nopadding">
                <?php echo $this->AppForm->create($modelClass, array('class' => $requestHandler, 'classForm' => 'form-horizontal form-bordered'));?>
                    <?php echo $this->form->hidden('q', array('value' => $requestHandler));?>
                    <!-- Carrega o campo de busca -->
                    <div class="span12" style="margin-left: 0px;">
                        <div class="span12">
                            <div style="border-top: 1px solid #E5E5E5;" class="control-group">
                                <label for="textfield" class="control-label"><?php echo __('Search')?></label>
                                <div class="controls">
                                    <div class="input-append input-prepend">
                                        <span class="add-on"><i class="icon-search"></i></span>
                                        <?php $value = isset($this->params['named']['search'])?$this->params['named']['search']:'';?>
                                        <?php echo $this->AppForm->input('search', array('label' => __('What are you looking for') . ", {$userLogged['given_name']}?", 'value' => $value, 'template' => 'form-input-clean', 'class' => 'input-xxlarge'));?>
                                        <button class="btn" type="button"><?php echo __('Search')?></button>&nbsp;
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php echo $this->AppForm->end(); ?>
            </div>
        </div>
    </div>
</div>