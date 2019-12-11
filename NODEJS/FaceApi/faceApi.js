const faceapi = require("face-api.js");
const canvas = require("canvas");
const fs = require("fs");
const path = require("path");

// mokey pathing the faceapi canvas
const { Canvas, Image, ImageData } = canvas;
faceapi.env.monkeyPatch({ Canvas, Image, ImageData });

const faceDetectionNet = faceapi.nets.ssdMobilenetv1;

// SsdMobilenetv1Options
const minConfidence = 0.5;

// TinyFaceDetectorOptions
const inputSize = 408;
const scoreThreshold = 0.5;

// MtcnnOptions
const minFaceSize = 50;
const scaleFactor = 0.8;

const baseDir = path.resolve(__dirname, './detected');

class FaceApi {

    getFaceDetectorOptions(net) {
        return net === faceapi.nets.ssdMobilenetv1
            ? new faceapi.SsdMobilenetv1Options({minConfidence})
            : (net === faceapi.nets.tinyFaceDetector
                    ? new faceapi.TinyFaceDetectorOptions({inputSize, scoreThreshold})
                    : new faceapi.MtcnnOptions({minFaceSize, scaleFactor})
            )
    }

    // simple utils to save files
    saveFile(fileName, buf) {
        if (!fs.existsSync(baseDir)) {
            fs.mkdirSync(baseDir);
        }
        // this is ok for prototyping but using sync methods
        // is bad practice in NodeJS
        fs.writeFileSync(path.resolve(baseDir, fileName), buf);
    }

    // TODO velocizzare il più possibile il tutto
    // TODO implementare anche un eventuale input in base64
    async evaluate(urlImage) {
        const faceDetectionOptions = this.getFaceDetectorOptions(faceDetectionNet);
        // load weights
        await faceDetectionNet.loadFromDisk('FaceApi/models');
        await faceapi.nets.faceLandmark68Net.loadFromDisk('FaceApi/models');
        await faceapi.nets.faceExpressionNet.loadFromDisk('FaceApi/models');
        // await faceapi.nets.faceRecognitionNet.loadFromDisk('FaceApi/models');
        await faceapi.nets.ageGenderNet.loadFromDisk('FaceApi/models');

        // load the image
        const img = await canvas.loadImage(urlImage);

        // create a new canvas and draw the detection and landmarks
        const out = faceapi.createCanvasFromMedia(img);

        // detect the faces with landmarks
        //const results = await faceapi.detectAllFaces(img, faceDetectionOptions).withFaceLandmarks();
        // faceapi.draw.drawDetections(out, results.map(res => res.detection));

        // faceapi.detectFaceLandmarks(out, results.map(res => res.landmarks), { drawLines: true, color: 'red' });

        const singleFace = await faceapi.detectSingleFace(img, faceDetectionOptions).withFaceLandmarks().withAgeAndGender().withFaceExpressions();
        const {age, gender, expressions} = singleFace;
        let expression = 0;
        let expressionValue = 0;
        // console.log(expressions);
        Object.keys(expressions).forEach(item => {
            if (expressions[item] > expressionValue) {
                expressionValue = expressions[item];
                expression = item;
            }
        });
        /*console.log(`età: ${age}`);
        console.log(`sesso: ${gender}`);
        console.log(`espressione: ${expression}`);*/
        await faceapi.draw.drawDetections(out, singleFace.detection);

        // save the new canvas as image
        this.saveFile(`${new Date().toISOString()}.jpg`, out.toBuffer('image/jpeg'));

        return {age, gender, expression};
    }
}

module.exports = FaceApi;