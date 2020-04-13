@extends('neutrino::admin.template.header-footer')
@section('title', 'Products | ')
@section('content')
	<div class="container">
		<div class="content full">
			<div class="title-search">
				<h2>Products <a class="headline-btn" href="/admin/product" role="button">Create New Product</a></h2>
				<div class="object-search">
					<form class="search-form" action="{{url()->full()}}" method="get">
						<input type="text" name="s" value="{{ request('s') }}" placeholder="Search products" autocomplete="off">
						<button type="submit"><i class="fas fa-search"></i></button>
					</form>
				</div>
			</div>

			<div class="pages-options-row text-right">
				<a class="trash-link" href="/admin/products-trash"><i class="fal fa-trash-alt"></i> Trashed ({{ $trashed }})</a>
			</div>

			<div class="responsive-table">
				<table cellpadding="0" cellspacing="0" class="table">
					<thead>
						<tr>
    						<th width="120"></th>
							<th class="text-left">@sortablelink('title', 'Title')</th>
							<th width="100">@sortablelink('status', 'Status')</th>
							<th>Created By</th>
							<th>Updated By</th>
							<th></th>
							<th width="60"></th>
						</tr>
					</thead>
					<tbody>
					@foreach( $products as $product )
						<tr>
                            <td data-label="Image">
                                @if($product->featuredImage)
                                <div class="object-edit-wrapper">
                                <a href="/admin/product/{{ $product->id }}">
                                    <img src="{{ $product->featuredImage->file_path}}" alt="{{ $product->title }}">
                                </a>
                                <div class="object-editing {{ $product->editing && $product->editing->object_id === $product->id? '' : 'hide' }}" data-editing-object-type="product" data-editing-object-id="{{ $product->id }}">
                                </div>
                                </div>
                                @endif
                            </td>
							<td data-label="Title">
                                <div class="object-edit-wrapper">
								    <a href="/admin/product/{{ $product->id }}">{{ $product->title }}</a>
                                    <div class="object-editing {{ $product->editing && $product->editing->object_id === $product->id? '' : 'hide' }}" data-editing-object-type="product" data-editing-object-id="{{ $product->id }}">
                                        @if( $product->editing && $product->editing->object_id === $product->id )
                                        {{ $product->editing->user->name }} is currently editing.
                                        @endif
                                    </div>
                                </div>
							</td>
							<td data-label="Status" class="text-center">
								{{ _translateStatus($product->status) }}
							</td>
							<td data-label="Created by" class="center">{{ $product->createdUser->name }}</td>
							<td data-label="Updated by" class="center">{{ $product->updatedUser->name }}</td>
							<td>
    							@if( $product->protected )
    							<i class="fal fa-lock"></i>
    							@endif
							</td>
							<td data-label="Delete" class="text-center">
								<form class="delete-form" action="/admin/products/{{ $product->id }}" method="post">
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

			<div class="pagination-links">
				{{ $products->appends($_GET)->links() }}
			</div>
		</div>

	</div>
@endsection

@section('js')
<script>
window.object_type = 'product';
</script>
@endsection
