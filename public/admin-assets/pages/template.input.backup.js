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
    const loading = $('#loading');
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
    var text=[];
    const certificateInputForm=$('#certificate-input-form');
    const base64Certificate=$('#base64Certificate');
    certificateInputForm.on('submit',function (e){
        loading.show();
        return new Promise((resolve, reject) => {
            let base64=document.getElementById('myCanvas').toDataURL().split(';base64,')[1];
            base64Certificate.val(base64)
            $(this).submit();
        })

    })

    let loadImageOnCanvasAndThenWriteText = (
        canvas,
        imageUrl,
        textStyleOptions,
    ) => {
        // Get the 2D Context from the canvas
        let ctx = canvas.getContext("2d");

        // Create a new Image
        let img = new Image();

        // Setting up a function with the code to run after the image is loaded
        img.onload = () => {
            // Once the image is loaded, we will get the width & height of the image
            let loadedImageWidth = img.width;
            let loadedImageHeight = img.height;

            console.log(img.width);

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
            ctx.textAlign = textStyleOptions.textAlign;

            // Setting this so that the postion of the text can be set
            // based on the x and y cord from the top right corner
            ctx.textBaseline = "top";
            let xCordOfText = 0;
            let yCordOfText = 0;
            let x = 0;
            let y = 0;
            let bounding = canvas.getBoundingClientRect();
            for (let record of data) {
                let inputElement = $(`input#${record.type}`)
                let font_size = $(`.font_size[data-type=${record.type}]`).val();
                ctx.font = `${font_size}px ${textStyleOptions.fontFamily}`;
                let width = parseFloat(record.width);
                let height = parseFloat(record.height);
                // ctx.width = width;

                console.log(record);

                let textToWrite = inputElement.val();

                xCordOfText = parseFloat(record.real_x);

                yCordOfText = parseFloat(record.real_y);

                x = parseFloat(record.x);
                y = parseFloat(record.y);

                let additional = JSON.parse(record.additional)

                let accept_ratio_image=additional.canvas.naturalWidth/additional.canvas.width


                console.log(bounding);

                // xCordOfText = ((img.width - bounding.x) - (xCordOfText) + bounding.x);
                //
                // yCordOfText = yCordOfText + (height);



                let start_x=bounding.x;

                xCordOfText=(start_x+(additional.box.left*accept_ratio_image)+additional.data.width)-bounding.x;

                yCordOfText=(additional.box.top*accept_ratio_image);

                ctx.fillText(textToWrite,xCordOfText,yCordOfText)
            }


            // // Get lines array
            // let arrayOfLines = getLines(ctx, textToWrite, textBoundingBoxWidth);
            // // Set line height as a little bit bigger than the font size
            // // let lineheight = textStyleOptions.fontSize + 10;
            //
            // // Loop over each of the lines and write it over the canvas
            // for (let i = 0; i < arrayOfLines.length; i++) {
            //     ctx.fillText(arrayOfLines[i], xCordOfText, yCordOfText,textBoundingBoxWidth);
            // }
        };

        // Now that we have set up the image "onload" handeler, we can assign
        // an image URL to the image.
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

});