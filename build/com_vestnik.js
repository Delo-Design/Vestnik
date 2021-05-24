const entry = {
	wall: {
		import: './com_vestnik/es6/wall.es6',
		filename: 'wall.js',
	},
	admin: {
		import: './com_vestnik/scss/admin.scss',
		filename: 'admin.delete',
	},
	images: {
		import: ['./com_vestnik/images/no-image.svg'],
		filename: 'images.clean',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../com_vestnik/media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]