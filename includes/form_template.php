<?php
/**
 * Template de formulário reutilizável
 * 
 * @param array $config Configurações do formulário
 * @param array $fields Campos do formulário
 * @param array $data Dados para preencher o formulário (opcional)
 */
function render_form($config, $fields, $data = []) {
    $form_id = $config['id'] ?? 'form-' . uniqid();
    $method = $config['method'] ?? 'POST';
    $action = $config['action'] ?? '';
    $title = $config['title'] ?? '';
    $description = $config['description'] ?? '';
    $submit_text = $config['submit_text'] ?? 'Salvar';
    $cancel_url = $config['cancel_url'] ?? '';
    $validate = isset($config['validate']) && $config['validate'] ? 'data-validate' : '';
    ?>
    
    <div class="form-container">
        <?php if ($title || $description): ?>
            <div class="form-header">
                <?php if ($title): ?>
                    <h2 class="form-title"><?php echo htmlspecialchars($title); ?></h2>
                <?php endif; ?>
                
                <?php if ($description): ?>
                    <p class="form-description"><?php echo htmlspecialchars($description); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="<?php echo $form_id; ?>" method="<?php echo $method; ?>" action="<?php echo $action; ?>" <?php echo $validate; ?>>
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <?php foreach ($fields as $field): ?>
                <?php render_form_field($field, $data); ?>
            <?php endforeach; ?>

            <div class="form-actions">
                <?php if ($cancel_url): ?>
                    <a href="<?php echo $cancel_url; ?>" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($submit_text); ?></button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Renderiza um campo de formulário
 * 
 * @param array $field Configurações do campo
 * @param array $data Dados para preencher o campo
 */
function render_form_field($field, $data) {
    $type = $field['type'] ?? 'text';
    $name = $field['name'] ?? '';
    $label = $field['label'] ?? '';
    $placeholder = $field['placeholder'] ?? '';
    $required = isset($field['required']) && $field['required'] ? 'required' : '';
    $validate = $field['validate'] ?? '';
    $value = $data[$name] ?? $field['value'] ?? '';
    $help = $field['help'] ?? '';
    $mask = $field['mask'] ?? '';
    $options = $field['options'] ?? [];
    $attributes = $field['attributes'] ?? [];
    
    // Converte atributos em string
    $attr_string = '';
    foreach ($attributes as $key => $val) {
        $attr_string .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
    }

    // Classes CSS
    $input_class = 'form-control';
    if (isset($field['class'])) {
        $input_class .= ' ' . $field['class'];
    }

    // Validação
    $validate_attr = '';
    if ($validate) {
        $validate_attr = 'data-validate="' . htmlspecialchars($validate) . '"';
    }

    // Máscara
    $mask_attr = '';
    if ($mask) {
        $mask_attr = 'data-mask="' . htmlspecialchars($mask) . '"';
    }
    ?>

    <div class="form-group">
        <?php if ($label): ?>
            <label for="<?php echo $name; ?>" class="form-label<?php echo $required ? ' required' : ''; ?>">
                <?php echo htmlspecialchars($label); ?>
            </label>
        <?php endif; ?>

        <?php switch ($type):
            case 'textarea': ?>
                <textarea 
                    name="<?php echo $name; ?>" 
                    id="<?php echo $name; ?>"
                    class="<?php echo $input_class; ?>"
                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                    <?php echo $required; ?>
                    <?php echo $validate_attr; ?>
                    <?php echo $attr_string; ?>
                    rows="<?php echo $field['rows'] ?? '3'; ?>"
                ><?php echo htmlspecialchars($value); ?></textarea>
                <?php break;

            case 'select': ?>
                <select 
                    name="<?php echo $name; ?>" 
                    id="<?php echo $name; ?>"
                    class="form-select <?php echo $input_class; ?>"
                    <?php echo $required; ?>
                    <?php echo $validate_attr; ?>
                    <?php echo $attr_string; ?>
                >
                    <?php if ($placeholder): ?>
                        <option value=""><?php echo htmlspecialchars($placeholder); ?></option>
                    <?php endif; ?>
                    
                    <?php foreach ($options as $option_value => $option_label): ?>
                        <option 
                            value="<?php echo htmlspecialchars($option_value); ?>"
                            <?php echo $value == $option_value ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php break;

            case 'radio': 
            case 'checkbox': ?>
                <?php foreach ($options as $option_value => $option_label): ?>
                    <div class="form-check">
                        <input 
                            type="<?php echo $type; ?>"
                            name="<?php echo $name; ?><?php echo $type === 'checkbox' ? '[]' : ''; ?>"
                            id="<?php echo $name . '_' . $option_value; ?>"
                            value="<?php echo htmlspecialchars($option_value); ?>"
                            class="form-check-input"
                            <?php echo $required; ?>
                            <?php echo $validate_attr; ?>
                            <?php echo $attr_string; ?>
                            <?php 
                            if ($type === 'checkbox') {
                                echo is_array($value) && in_array($option_value, $value) ? 'checked' : '';
                            } else {
                                echo $value == $option_value ? 'checked' : '';
                            }
                            ?>
                        >
                        <label class="form-check-label" for="<?php echo $name . '_' . $option_value; ?>">
                            <?php echo htmlspecialchars($option_label); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php break;

            case 'switch': ?>
                <div class="form-switch">
                    <input 
                        type="checkbox"
                        name="<?php echo $name; ?>"
                        id="<?php echo $name; ?>"
                        class="form-check-input"
                        <?php echo $required; ?>
                        <?php echo $validate_attr; ?>
                        <?php echo $attr_string; ?>
                        <?php echo $value ? 'checked' : ''; ?>
                    >
                    <?php if ($label): ?>
                        <label class="form-check-label" for="<?php echo $name; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    <?php endif; ?>
                </div>
                <?php break;

            case 'file': ?>
                <div class="form-file">
                    <input 
                        type="file"
                        name="<?php echo $name; ?>"
                        id="<?php echo $name; ?>"
                        class="form-file-input"
                        <?php echo $required; ?>
                        <?php echo $validate_attr; ?>
                        <?php echo $attr_string; ?>
                    >
                    <label class="form-file-label" for="<?php echo $name; ?>">
                        <?php echo $placeholder ?: 'Escolher arquivo...'; ?>
                    </label>
                </div>
                <?php break;

            default: ?>
                <input 
                    type="<?php echo $type; ?>"
                    name="<?php echo $name; ?>"
                    id="<?php echo $name; ?>"
                    class="<?php echo $input_class; ?>"
                    value="<?php echo htmlspecialchars($value); ?>"
                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                    <?php echo $required; ?>
                    <?php echo $validate_attr; ?>
                    <?php echo $mask_attr; ?>
                    <?php echo $attr_string; ?>
                >
                <?php break;
        endswitch; ?>

        <?php if ($help): ?>
            <div class="form-text"><?php echo htmlspecialchars($help); ?></div>
        <?php endif; ?>
    </div>
    <?php
}