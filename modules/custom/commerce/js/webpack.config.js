var path = require('path');
module.exports = {
  entry: './components/index.js',
  //entry: ['./components/index.js', './components/UserCard.js', './components/AuthorList.js'],
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-react']
          }
        }
      },
      {
        test: /\.css$/,
        use: ['style-loader','css-loader'],
      },
      {
        test: /\.(jpg|png|jpeg|svg)$/,
        use: {
          loader: 'url-loader',
        },
      },
    ]
  }
}
