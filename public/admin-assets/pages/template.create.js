$(document).ready(function () {

    const config = {
        autoCrop: false,
        zoomable: false,
        zoomOnWheel: false,
        zoomOnTouch: false,
    }

    const data = $('#data');

    const isEdit = data.length !== 0;

    let rectangles = [];

    const nameValueModal = $('#nameValueModal');
    const areaImage = $('#areaImage');
    const modalSaveButton = $('#modalSaveButton');
    const inputTemplatesContent = $('#inputTemplates');
    const typeRectangle = $('#typeRectangle');
    const imageFile = $('#imageFile');
    let image;
    let cropper;
    let firstTime = true;

    if (isEdit) {
        image = document.getElementById('image')
        let types = JSON.parse(data.val());
        for (let key in types)
            rectangles.push(types[key])

        cropper = new Cropper(image, config);
        firstTime = false;
    }

    function clearAll() {
        rectangles = [];
        renderRectangles();
    }

    if (!firstTime)
        renderRectangles();


    imageFile.on('change', function (event) {
        if (cropper)
            clearAll();

        const [file] = (this).files
        if (file) {
            if (firstTime) {
                image = document.createElement('img');
                image.src = URL.createObjectURL(file)
                areaImage.append(image);
                cropper = new Cropper(image, config);
                firstTime = false;
            } else {
                cropper.replace(URL.createObjectURL(file))
            }
        }
        imageFile.blur();
    })
    $(window).on('keyup', function (e) {
        if (e.keyCode === 13)
            nameValueModal.modal('show');

    });
    modalSaveButton.on('click', function () {


        let type = typeRectangle.val();

        let position = cropper.getCropBoxData();
        let coordinate = cropper.getData();
        const is_signature = type === 'signature';

        rectangles.push(
            {
                type: type,
                x: position.left,
                y: position.top,
                real_x: coordinate.x,
                real_y: coordinate.y,
                width: position.width,
                height: position.height,
                image: is_signature ? '/assets/images/placeholder.png' : null,
                color: is_signature ? '#1599d6' : '#000000',
                line_number: 1,
                additional: {
                    data: cropper.getData(),
                    container: cropper.getContainerData(),
                    image: cropper.getImageData(),
                    canvas: cropper.getCanvasData(),
                    box: cropper.getCropBoxData(),
                }
            }
        )

        renderRectangles();

        nameValueModal.modal('hide');

        cropper.clear();

    });

    function renderRectangles() {
        $('.rect').remove();

        cropper.clear();
        inputTemplatesContent.html('');
        for (let i = 0; i < rectangles.length; i++) {
            let rectangle = rectangles[i];
            areaImage.append(`
                            <div style="width:${parseFloat(rectangle.width)}px;height:${parseFloat(rectangle.height)}px;left:${parseFloat(rectangle.x)}px;top:${parseFloat(rectangle.y)}px"
                             class="rect">
                                <div class="exclusion-x"></div>
                                <div class="exclusion-y"></div>
                            </div>
                 `)
            inputTemplatesContent.append(template(rectangle, i));
        }
    }

    inputTemplatesContent.on('click', '.remove-rect', function () {
        // console.log(inputTemplatesContent.index(this));
        let index = $(this).parents('.card').index();

        rectangles.splice(index, 1)

        renderRectangles();
    });
    inputTemplatesContent.on('input', 'input:not(.signature-upload)', function () {
        let index = $(this).parents('.card').index();
        let type = $(this).data('type');
        rectangles[index][type] = $(this).val();
        renderRectangles();
    })

    $('#inputTemplates').on('change', '.signature-upload', function () {
        let index = $(this).parents('.card').index();
        let type = $(this).data('type');
        let self = $(this);
        const FR = new FileReader();
        FR.addEventListener("load", function (evt) {
            let card=self.parent().parent().parent();
            console.log(self.siblings('.signature-image-base64'));
            card.find('.signature-image-base64').val(evt.target.result);
            card.find('.preview_image').attr('src', evt.target.result);

            rectangles[index]['image'] = evt.target.result;
        });
        FR.readAsDataURL(this.files[0]);
        // if (file) {
        //    let base64=URL.createObjectURL(file);
        //    console.log(base64);
        //    self.siblings('.signature-image-bas64').val(base64);
        //    self.parent().parent().parent().find('.preview_image').attr('src',base64);
        // }
    });


    function template(rectangle, i) {
        let additional = typeof rectangle.additional === 'string' ? rectangle.additional : JSON.stringify(rectangle.additional)
        if (rectangle.type === 'signature')
            return `
                    <div class="card mb-5" data-index="${i}">
                        <div class="card-body">
                      <div class="row mb-2">
            <div class="col-md-2">
                <label>Type</label>
                <input value="${rectangle.type}"  readonly type="text" class="form-control" name="data[${i}][type]">
            </div>
             <div class="col-md-2">
                <label>X</label>
                <input  value="${rectangle.x}" data-type="x"  type="number" class="form-control" name="data[${i}][x]">
            </div>
            <div class="col-md-2">
                <label>Y</label>
                <input  value="${rectangle.y}" data-type="y"  type="number" class="form-control" name="data[${i}][y]">
            </div>
              <div class="col-md-2">
                <label>Width</label>
                <input  value="${rectangle.width}" data-type="width"  type="number" class="form-control" name="data[${i}][width]">
            </div>
             <div class="col-md-2">
                <label>Height</label>
                <input  value="${rectangle.height}" data-type="height"  type="number" class="form-control" name="data[${i}][height]">
            </div>
            </div>
            <div class="row mt-3">
             <div class="col-md-2">
                <label>Real X</label>
                <input  value="${rectangle.real_x}" data-type="x"  type="number" class="form-control" name="data[${i}][real_x]">
            </div>
            <div class="col-md-2">
                <label>Real Y</label>
                <input  value="${rectangle.real_y}" data-type="y"  type="number" class="form-control" name="data[${i}][real_y]">
            </div>
            <div class="col-md-3">
                <label>Upload Signature</label>
                <input data-type="signature"  type="file" class="form-control signature-upload">
                <input type="hidden" name="data[${i}][image]" value="${rectangle.image}" class="signature-image-base64">
            </div>
            <input value=${additional} data-type="additional" type="hidden" name="data[${i}][additional]">
                <div class="col-md-6 mt-3">
                <button type="button" class="btn btn-danger remove-rect">Delete</button>
                </div>
            </div>
            <div class="row my-3">
            <img width="100" height="100" alt="Signature" src="${rectangle.image}" class="preview_image">
                </div>
                        </div>
                    </div>
        `
        else
            return `
                    <div class="card mb-5" data-index="${i}">
                        <div class="card-body">
                      <div class="row mb-2">
            <div class="col-md-2">
                <label>Type</label>
                <input value="${rectangle.type}"  readonly type="text" class="form-control" name="data[${i}][type]">
            </div>
             <div class="col-md-2">
                <label>X</label>
                <input  value="${rectangle.x}" data-type="x"  type="number" class="form-control" name="data[${i}][x]">
            </div>
            <div class="col-md-2">
                <label>Y</label>
                <input  value="${rectangle.y}" data-type="y"  type="number" class="form-control" name="data[${i}][y]">
            </div>
              <div class="col-md-2">
                <label>Width</label>
                <input  value="${rectangle.width}" data-type="width"  type="number" class="form-control" name="data[${i}][width]">
            </div>
             <div class="col-md-2">
                <label>Height</label>
                <input  value="${rectangle.height}" data-type="height"  type="number" class="form-control" name="data[${i}][height]">
            </div>
             <div class="col-md-2">
                <label>Line Number</label>
                <input value="${rectangle.line_number}"  type="number" class="form-control" name="data[${i}][line_number]">
            </div>
            </div>
            <div class="row mt-3">
            <div class="col-md-2">
                <label>Color</label>
                <input value="${rectangle.color}" style="padding: 6px;height: 0px;" type="color" class="form-control" name="data[${i}][color]">
            </div>
             <div class="col-md-2">
                <label>Real X</label>
                <input  value="${rectangle.real_x}" data-type="x"  type="number" class="form-control" name="data[${i}][real_x]">
            </div>
            <div class="col-md-2">
                <label>Real Y</label>
                <input  value="${rectangle.real_y}" data-type="y"  type="number" class="form-control" name="data[${i}][real_y]">
            </div>
            <input value=${additional} data-type="additional" type="hidden" name="data[${i}][additional]">
                <div class="col-md-6 mt-3">
                <button type="button" class="btn btn-danger remove-rect">Delete</button>
                </div>
            </div>
                        </div>
                    </div>
        `
    }
});
