
var underline = function(ctx, text, x, y, size, color, thickness ,offset){
    var width = ctx.measureText(text).width;

    switch(ctx.textAlign){
        case "center":
            x -= (width/2); break;
        case "right":
            x -= width; break;
    }

    y += size+offset;

    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.lineWidth = thickness;
    ctx.moveTo(x,y);
    ctx.lineTo(x+width,y);
    ctx.stroke();

}

String.prototype.toIndiaDigits= function(){
    var id= ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return this.replace(/[0-9]/g, function(w){
        return id[+w]
    });
}
function getMousePosition(canvas, event) {
    let rect = canvas.getBoundingClientRect();
    let x = event.clientX - rect.left;
    let y = event.clientY - rect.top;
    console.log("Coordinate x: " + x,
        "Coordinate y: " + y);
}
$(document).ready(function () {

    $('body').on('click','.font_weight_input',function (){

        setTimeout(()=>{
            let current_checked=$(this);
            let parent=$(this).parent().parent();
            if(current_checked.val() === 'normal'){
                parent.find('.btn.btn-outline-primary.active').removeClass('active');
                parent.find('.font_weight_input').prop('checked', false);
            }
        },100)

    })

    let additional_fields=[]
    const AddFieldButton=$('#add_field');

    const template_additional_field=()=>{
        let index =additional_fields.length;
        let new_record={
            'index':index,
            'label':`حقل إضافي ${index+1}`,
            'type':`additional_field_${index}`,
        };
        additional_fields.push(new_record)
        return `
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="${new_record.type}">${new_record.label}</label>
                                        <input class="form-control template-input" id="${new_record.type}" type="text"
                                               name="${new_record.type}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>حجم الخط</label>
                                        <input data-type="${new_record.type}" value="50" class="form-control font_size"
                                               type="number">
                                    </div>
                                    <div class="col-md-2">
                                        <label>لون الخط</label>
                                        <input data-type="${new_record.type}" value="#fff" class="form-control font_color" type="color">
                                    </div>
                                    <div class="col-md-2">
                                        <label>نوع الخط</label>
                                        <select data-type="${new_record.type}" class="form-control font_family" name="font_family" >
                                            <option selected value="Arial">Arial</option>
                                            <option value="Cairo">Cairo</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="tradoAr">Traditional Arabic</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="btn-group btn-group-toggle font_weights d-flex h-100 align-items-center" data-type="${new_record.type}" data-toggle="buttons">
                                            <label class="btn btn-outline-primary active">
                                                <input type="radio" value="normal" name="font_weight${new_record.type}"  checked> N
                                            </label>
                                            <label class="btn btn-outline-primary">
                                                <input type="radio" value="bold" name="font_weight${new_record.type}" > <b>B</b>
                                            </label>
                                            <label class="btn btn-outline-primary">
                                                <input type="radio" value="italic" name="font_weight${new_record.type}" > <i>i</i>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-1 align-self-center">
                                    <span data-index="${index}" class="delete_row"><i class="fa fa-trash text-danger"></i></span>
</div>
                            </div>
        `
    }

    const template_button_moving=function (record){
        return `
         <label class="btn btn-primary mx-2 additional_button_move record-${record.index}">
                            <input type="radio" name="selectedText" value="${record.index+original_data.length}">
                          ${record.label}
                        </label>
        `
    }

    $('#additional_fields').on('click','.delete_row',function (){
        let index = $(this).data('index')
        additional_fields=additional_fields.filter((x=>x.index!=index))
        $(`.additional_button_move.record-${index}`).remove()
        $(this).parent().parent().remove();
        PreviewButton.trigger('click');
    })
    AddFieldButton.on('click',function (e){
        e.preventDefault();
        let template=template_additional_field();
        let last_record = additional_fields[additional_fields.length-1];
        $('#additional_fields #fields').append(template);
        $('#buttons-for-moving').append(template_button_moving(last_record))


    })

    var canvas = document.getElementById("myCanvas");
    var ctx = canvas.getContext("2d");

// variables used to get mouse position on the canvas
    var $canvas = $("#myCanvas");
    var canvasOffset = $canvas.offset();
    var offsetX = canvasOffset.left;
    var offsetY = canvasOffset.top;
    var scrollX = $canvas.scrollLeft();
    var scrollY = $canvas.scrollTop();

// variables to save last mouse position
// used to see how far the user dragged the mouse
// and then move the text by that distance
    var startX;
    var startY;


    function getLines(ctx, text, maxWidth) {
        var words = text.split(" ");
        var lines = [];
        var currentLine = words[0];

        for (var i = 1; i < words.length; i++) {
            var word = words[i];
            var width = ctx.measureText(currentLine + " " + word).width;
            if (width < maxWidth) {
                currentLine += " " + word;
            } else {
                lines.push(currentLine);
                currentLine = word;
            }
        }
        lines.push(currentLine);
        return lines;
    }
    var texts=[];


    let loadImageOnCanvasAndThenWriteText = (
        canvas,
        imageUrl,
        textStyleOptions,
    ) => {
        texts=[];
        // Get the 2D Context from the canvas
        let ctx = canvas.getContext("2d");

        // Create a new Image
        let img = new Image();

        // Setting up a function with the code to run after the image is loaded
        img.onload = () => {
            // Once the image is loaded, we will get the width & height of the image
            let loadedImageWidth = img.width;
            let loadedImageHeight = img.height;


            // Set the canvas to the same size as the image.
            canvas.width = loadedImageWidth;
            canvas.height = loadedImageHeight;

            // Draw the image on to the canvas.
            ctx.drawImage(img, 0, 0);

            // Create a rectangle and fill the canvas with it
            // ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
            // ctx.fillRect(0, 0, loadedImageWidth, loadedImageHeight);

            // Set all the properties of the text based on the input params

            ctx.fillStyle = textStyleOptions.textColor;
            // ctx.textAlign = textStyleOptions.textAlign;

            // Setting this so that the postion of the text can be set
            // based on the x and y cord from the top right corner
            ctx.textBaseline = "top";
            let xCordOfText = 0;
            let yCordOfText = 0;
            let x = 0;
            let y = 0;
            let bounding = canvas.getBoundingClientRect();
            // {
            //     "type": "author_name",
            //     "x": "213",
            //     "y": "259.09999084472656",
            //     "real_x": "438.7640449438202",
            //     "real_y": "532.9775092307101",
            //     "width": "647.1999816894531",
            //     "height": "77.60000610351562",
            //     "line_number": "1",
            //     "color": "#000000",
            //     "additional": "{\"data\":{\"x\":438.7640449438202,\"y\":532.9775092307101,\"width\":1333.183482880896,\"height\":159.8501998387026,\"rotate\":0,\"scaleX\":1,\"scaleY\":1},\"container\":{\"width\":1068,\"height\":826},\"image\":{\"naturalWidth\":2200,\"naturalHeight\":1700,\"aspectRatio\":1.2941176470588236,\"width\":1068,\"height\":825.2727272727273,\"left\":0,\"top\":0},\"canvas\":{\"left\":0,\"top\":0.36363636363637397,\"width\":1068,\"height\":825.2727272727273,\"naturalWidth\":2200,\"naturalHeight\":1700},\"box\":{\"left\":213,\"top\":259.09999084472656,\"width\":647.1999816894531,\"height\":77.60000610351562}}"
            // }
            data=[...original_data];
            for(let field of additional_fields){
                data.push({
                    ...data[0],
                    'type':field.type,
                    'real_x':data[0].real_x+50,
                    'real_y':data[0].real_y+50,

                })
            }
            for (let record of data) {
                let inputElement = $(`input#${record.type}`)
                let font_size = $(`.font_size[data-type=${record.type}]`).val();
                let font_color = $(`.font_color[data-type=${record.type}]`).val();
                let font_family = $(`.font_family[data-type=${record.type}]`).val();
                let font_weights = $(`.font_weights[data-type=${record.type}] .active input`);
                let font_weight='';

                font_weights.each((i,ele)=>{
                    font_weight+=$(ele).val()+' ';
                })
                font_weight=font_weight.trim();

                ctx.font = `${font_size}px ${font_family}`;
                // let width = parseFloat(record.width);
                // let height = parseFloat(record.height);
                // ctx.width = width;


                let textToWrite = inputElement.val();

                xCordOfText = parseFloat(record.real_x);

                yCordOfText = parseFloat(record.real_y);

                x = parseFloat(record.x);
                y = parseFloat(record.y);

                let additional = JSON.parse(record.additional)

                let accept_ratio_image=additional.canvas.naturalWidth/additional.canvas.width


                // xCordOfText = ((img.width - bounding.x) - (xCordOfText) + bounding.x);
                //
                // yCordOfText = yCordOfText + (height);

                let start_x=bounding.x;

                xCordOfText=(start_x+(additional.box.left*accept_ratio_image)+additional.data.width)-bounding.x;

                yCordOfText=(additional.box.top*accept_ratio_image);
                console.log(font_weight);
                texts.push({
                    text:textToWrite,
                    x:xCordOfText,
                    y:yCordOfText,
                    additional:additional,
                    font:`${font_weight} ${font_size}px ${font_family}`,
                    color:font_color
                })
            }
            draw();

        };

        img.src = imageUrl;
    };
    let src = $('#ImageUrl').val();
    const PreviewButton = $('#preview-btn');

    PreviewButton.on('click', function () {

        // Setting up the canvas
        let theCanvas = document.getElementById("myCanvas");

        // Some image URL..
        let imageUrl =
            src;

        let textStyleOptions = {
            fontSize: $('#font_size').val(),
            fontFamily: $('#font_family').val(),
            textColor: $('#font_color').val(),
            textAlign: "right",
        };

        // Load image on the canvas & then write text
        loadImageOnCanvasAndThenWriteText(
            theCanvas,
            imageUrl,
            textStyleOptions,
        );
    });


    for (var i = 0; i < texts.length; i++) {
        var text = texts[i];
        text.width = ctx.measureText(text.text).width;
        text.height = 16;
    }

// this var will hold the index of the selected text
    var selectedText = -1;


// clear the canvas draw all texts
    function draw() {
        // ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Create a new Image
        let img = new Image();

        // Setting up a function with the code to run after the image is loaded
        img.onload = () => {


            // Draw the image on to the canvas.
            ctx.drawImage(img, 0, 0)
            let bounding = canvas.getBoundingClientRect();
            for (var i = 0; i < texts.length; i++) {
                var text = texts[i];
                ctx.fillStyle = text.color;
                ctx.font = text.font;
                // Here
                ctx.fillText(text.text, text.x, text.y);

            }

        };

        img.src = src;

    }
    function DistSquared(pt1, pt2) {
        var diffX = pt1.x - pt2.x;
        var diffY = pt1.y - pt2.y;
        return (diffX*diffX+diffY*diffY);
    }


// test if x,y is inside the bounding box of texts[textIndex]
    function textHittest(x, y, textIndex) {
        let closest = texts[0];
        let myPosition={x,y}
        let shortestDistance = DistSquared(myPosition, texts[0]);
        for (i = 0; i < texts.length; i++) {
            var d = DistSquared(myPosition, texts[i]);
            if (d < shortestDistance) {
                closest = texts[i];
                shortestDistance = d;
            }
        }
        console.log(shortestDistance);
        // return (x >= text.x && x <= text.x + text.width && y >= text.y - text.height && y <= text.y);
        return true;
    }

// handle mousedown events
// iterate through texts[] and see if the user
// mousedown'ed on one of them
// If yes, set the selectedText to the index of that text
    function handleMouseDown(e) {
        e.preventDefault();
        startX = parseInt(e.clientX - offsetX);
        startY = parseInt(e.clientY - offsetY);
        selectedText=$(`input[name='selectedText']:checked`).val();
        $('#myCanvas').css('cursor','pointer');
        // Put your mousedown stuff here
        // for (var i = 0; i < texts.length; i++) {
        //     if (textHittest(startX, startY, i)) {
        //         $('#myCanvas').css('cursor','pointer');
        //         selectedText = i;
        //     }
        // }
    }

// done dragging
    function handleMouseUp(e) {
        e.preventDefault();
        selectedText = -1;
        $('#myCanvas').css('cursor','auto');
    }

// also done dragging
    function handleMouseOut(e) {
        e.preventDefault();
        selectedText = -1;
    }

// handle mousemove events
// calc how far the mouse has been dragged since
// the last mousemove event and move the selected text
// by that distance
    function handleMouseMove(e) {
        if (selectedText < 0) {
            return;
        }
        e.preventDefault();
        mouseX = parseInt(e.clientX - offsetX);
        mouseY = parseInt(e.clientY - offsetY);

        // Put your mousemove stuff here
        var dx = mouseX - startX;
        var dy = mouseY - startY;
        startX = mouseX;
        startY = mouseY;
        console.log(selectedText);
        var text = texts[selectedText];
        text.x += dx*2;
        text.y += dy*2;
        draw();
    }

// listen for mouse events
    $("#myCanvas").mousedown(function (e) {
        handleMouseDown(e);
    });
    $("#myCanvas").mousemove(function (e) {
        handleMouseMove(e);
    });
    $("#myCanvas").mouseup(function (e) {
        handleMouseUp(e);
    });
    $("#myCanvas").mouseout(function (e) {
        handleMouseOut(e);
    });
});