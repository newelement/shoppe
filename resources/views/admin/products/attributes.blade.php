@extends('neutrino::admin.template.header-footer')
@section('title', 'Product Attributes | ')
@section('content')
        <div class="container">
            <div class="content">

                <h2>Product Attributes</h2>

                <div class="responsive-table">
                    <table cellpadding="0" cellspacing="0" class="table">
                        <thead>
                            <tr>
                                <th class="text-left">Name</th>
                                <th class="text-left">Values</th>
                                <th width="80"></th>
                                <th width="60">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach( $attributes as $attr )
                            <tr>
                                <td><a href="/admin/product-attributes/{{ $attr->id }}">{{ $attr->name }}</a></td>
                                <td>{{ $attr->values }}</td>
                                <td class="text-center"><a href="/admin/product-attributes/{{ $attr->id }}">Edit</a></td>
                                <td class="text-center">
                                    <form action="/admin/product-attributes/{{ $attr->id }}" method="post">
                                    @method('delete')
                                    @csrf
                                        <button type="submit" class="delete-btn">&times;</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

            <aside class="sidebar">
            @if( $edit->id )
                <form action="/admin/product-attributes/{{ $edit->id }}" method="post">
            @else
                <form action="/admin/product-attributes" method="post">
            @endif
                    @csrf
                    <div class="side-fields">
                        <div class="form-row">
                            <label class="label-col" for="name">Attribute Name</label>
                            <div class="input-col">
                                <input id="name" type="text" name="name" value="{{ old('name', $edit->name) }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="label-col" for="values">Attribute Values</label>
                            <div class="input-col">
                                <textarea id="values" type="text" name="values" placeholder="Red|Blue|Green">{{ old('values', $edit->values) }}</textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                        @if( $edit->id )
                            <button type="submit" class="btn form-btn">Update</button>
                        @else
                            <button type="submit" class="btn form-btn">Create</button>
                        @endif
                        </div>

                    </div>
                </form>
            </aside>

        </div>
@endsection
