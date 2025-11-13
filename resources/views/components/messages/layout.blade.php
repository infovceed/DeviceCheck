@if(count($messages) > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <h4 class="card-title">{{__('Messages')}}</h4>
                        @foreach($messages as $message)
                            <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column my-2">
                                <span><strong>{{ $message->user->name }}</strong>:</span>
                                <span>{!! $message->message !!}</span>
                                <small>{{ $message->created_at->diffForHumans() }}</small>
                                @if(isset($message->attachment)&&$message->attachment->count() > 0)
                                    <hr>
                                    <div class="row mt-2">
                                        @foreach($message->attachment as $attachment)
                                           <div class="col-12 col-lg d-flex">
                                                <a href="{{ $attachment->url()}}" class="btn btn-light btn-rounded btn-sm" download target="_blank">
                                                <x-orchid-icon path="bs.download" class="d-inline mx-1"/> {{ strlen($attachment->original_name) > 20 ? substr($attachment->original_name, 0, 20) . '...' : $attachment->original_name }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
@if(count($messages) == 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <h4 class="card-title">{{__('Messages')}}</h4>
                    <div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column my-2">
                        <span>{{__('No messages found')}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
