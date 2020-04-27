                        <form action="/admin/shoppe-settings/products" method="post">
                            @csrf
                            <h3>Product Categories</h3>

                            <div class="form-row">
                                <div class="label-col">Show Empty Categories</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="show_empty_categories" {{ getShoppeSetting('show_empty_categories', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                                <span class="input-notes">
                                    <span class="note">Show empty product categories in the menu and grid</span>
                                </span>
                            </div>

                            <div class="form-row">
                                <div class="label-col">Show Empty Filters</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="show_empty_filters" {{ getShoppeSetting('show_empty_filters', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                                <span class="input-notes">
                                    <span class="note">Show empty product filters in the menu and grid</span>
                                </span>
                            </div>

                            <div class="form-row">
                                <div class="label-col">Product Tags in Menu</div>
                                @php
                                $tags = getShoppeSetting('product_menu_tags', $settings->product);
                                @endphp
                                <div class="input-col">
                                    <select name="product_menu_tags[]" multiple size="6">
                                        @foreach( $settings->taxonomies as $term )
                                        <option value="{{ $term->slug }}" {{ in_array( $term->slug, (array) $tags )? 'selected="selected"' : '' }}>{{ $term->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <span class="input-notes">
                                    <span class="note">Select multitple. Show which product tags show up in the filter menu.</span>
                                </span>
                            </div>

                            <h3>Products Display</h3>

                            <div class="form-row">
                                <div class="label-col">Show Cart Buttons in Grid</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="show_grid_add_cart" {{ getShoppeSetting('show_grid_add_cart', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="label-col">Show Price in Grid</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="show_product_grid_pricing" {{ getShoppeSetting('show_product_grid_pricing', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="label-col">Show Product Sorting</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="show_product_sorting" {{ getShoppeSetting('show_product_sorting', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                            </div>

                            <h3>Inventory</h3>

                            <div class="form-row">
                                <div class="label-col">Manage Stock</div>
                                <div class="input-col has-checkbox">
                                    <label><input type="checkbox" value="1" name="manage_stock" {{ getShoppeSetting('manage_stock', $settings->product) === 1? 'checked="checked"' : '' }}> <span>Yes</span></label>
                                </div>
                                <span class="input-notes">
                                    <span class="note">Monitor stock levels and receive notifications on low levels</span>
                                </span>
                            </div>

                            <div class="form-row">
                                <div class="label-col" for="stock-threshold">Stock Threshold</div>
                                <div class="input-col">
                                    <input type="number" name="stock_threshold" value="{{ getShoppeSetting('stock_threshold', $settings->product) }}">
                                </div>
                                <span class="input-notes">
                                    <span class="note">Low stock level</span>
                                </span>
                            </div>

                            <button type="submit" class="btn">Save Product Settings</button>

                        </form>

