function getRandomInt(min, max) {
    if (!min) min = 0;
    if (!max) max = 99999;
    return Math.floor(Math.random() * (max - min + 1)) + min;
}