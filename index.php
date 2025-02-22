<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>MIXOBS - Мix Observatory</title>
	<link rel="stylesheet" href="http://localhost/res/style.css?v=<? echo time(); ?>">
</head>
<body>

	<div class="alert-overlay hidden">
		<div class="message"></div>
	</div>
	<div class="toppanel-container">
		<div class="toppanel">
			<div class="logotype"></div>
			
			<div class="topnav">
				<a href="#">Podcasts</a>
				<a href="#">Lives</a>
				<a href="#">Videos</a>
				<a href="#">Schedule</a>
				<a href="#">Sign in</a>
			</div>
			 <!-- player-hidden -->
			<div class="player-container">

				<div class="seek-bar">
						<div class="seek-position"></div>
						<div class="seek-zone"></div>
				</div>

				<div class="player">

					<div class="play-control-group">
						<div class="prev-button"></div>
						<div class="play-button"></div>
						<div class="next-button"></div>
					</div>

					<div class="play-timer"></div>

					<div id="analyserContainer"></div>
					
					<div class="album-information-container">
						<div class="episode-title"><div class="text-slider"></div></div>
						<div class="track-name"><div class="text-slider"></div></div>
					</div>
					
					<div class="sound-control-group">
						<div class="vol-button"></div>
						<div class="eq-button"></div>
					</div>
					
					<div class="vol-bar">
						<div class="vol-position"></div>
						<div class="vol-zone"></div>
					</div>

					<div id="equalizerContainer" class="equalizer-hidden">
						<div class="equalizer-line">
							<div class="eq-switch"><div class="eq-sw-position"></div></div>
							<div class="vis-switch"><div class="vis-sw-position"></div></div>
						</div>
					</div>

				</div>

			</div>

		</div>
	</div>

	<div class="container">

		<div class="document">

			<div class="podcast-list"></div>

			<div class="episode-list"></div>

</div>

</div>

<div class="footer"></div>

<script>
	document.addEventListener("DOMContentLoaded", () => {
		request('api', {
			action: 'get-podcasts'
		}).then((json) => {
			response(json);
		}).catch((err) => {
			console.error(err.message);
		});

		function response(data){
			loadPodcasts(data);
		}	
	});


	function showEpisodes(podcast_id, cover){

		window.scrollTo(0, 0);

		request('api', {
			action: 'get-episodes',
			podcast_id: podcast_id
		}).then((json) => {
			response(json);
		}).catch((err) => {
			console.error(err.message);
		});

		function response(data){

			localStorage.setItem('episodes', JSON.stringify(data));

			if(data.result == 0){
				console.error('Not found.');
				return;
			}
			
			const doc = QS('.document');
			const episodeList = QS('.episode-list');
			const podcastList = QS('.podcast-list');
			
			episodeList.innerHTML = '';

			episodeList.style.display = 'block';
			doc.classList.add('overlay');

			const overlay = QS('.overlay');

			

			overlay.onclick = (e) =>{
				if (e.target.classList.contains('document')) {
					overlay.onclick = null;
					doc.classList.remove('overlay');
					episodeList.style.display = 'none';
					restoreContainer();
				}
			}

			
			const episodeImg = new Image();
			
			episodeImg.src = 'http://data.localhost/art/' + cover;
			episodeList.append(episodeImg);
			
			
			for (var i = 0; i < data.length; i++) {
				const episodeItem = document.createElement("div");
				const episodeName = document.createElement("div");
				const episodeRelDate = document.createElement("div");
				const episode_id = data[i].episode_id;
				const episodeTListContainer = document.createElement("div");
				episodeItem.classList.add('episode-item');
				episodeTListContainer.classList.add('episode-list-container');
				episodeName.classList.add('episode-list-header');
				episodeRelDate.classList.add('episode-rel-date');
				episodeName.innerHTML = data[i].episode_name;
				episodeRelDate.innerHTML = data[i].release_date;
				episodeItem.append(episodeName);
				episodeItem.append(episodeTListContainer);
				episodeItem.append(episodeRelDate);
				episodeList.append(episodeItem);

				resizeContainer();

				episodeItem.onclick = (e) => {
					if (e.target.classList.contains('episode-item') || e.target.classList.contains('episode-list-container') || e.target.classList.contains('episode-list-header') || e.target.classList.contains('episode-rel-date')){
							
						let clearEA = QSA('.episode-loaded');
						for (var i = clearEA.length - 1; i >= 0; i--) {
							clearEA[i].classList.remove('episode-loaded');
						}

						episodeItem.classList.add('episode-loaded');

	
						loadPlayer(episode_id, 0);
						
						request('api', {
							action: 'get-tracklist',
							episode_id: episode_id
						}).then((json) => {
							response(json);
						}).catch((err) => {
							console.error(err.message);
						});


						function response(tracklist_data){

							localStorage.setItem('playlist', JSON.stringify(tracklist_data));

							let clearList = QSA('.episode-list-container');
							for (var i = clearList.length - 1; i >= 0; i--) {
								clearList[i].innerHTML = '';
							}

							if (tracklist_data.result == 0) {
								console.log('No timecode data');
								const tracklistItem = document.createElement("div");
								tracklistItem.classList.add('tracklist-no-tc');
								tracklistItem.innerHTML = 'No timecode data';
								episodeTListContainer.append(tracklistItem);
								return;
							}
						

							for (var i = tracklist_data.length - 1; i >= 0; i--) {
								let timecode = tracklist_data[i].timecode;
								let eId = tracklist_data[i].episode_id;

								const tracklistItem = document.createElement("div");

								tracklistItem.classList.add('tracklist-item');
								tracklistItem.innerHTML = tracklist_data[i].name;
								tracklistItem.title = secToStr(tracklist_data[i].timecode);
								tracklistItem.dataset.episode_id = episode_id;
								tracklistItem.dataset.timecode = timecode;

								tracklistItem.onclick = (e) => {
									loadPlayer(episode_id, timecode);
								};

								
								episodeTListContainer.append(tracklistItem);
							}

						}

					} 

				}


			}

			const getMoreEpisodes = document.createElement("div");
			getMoreEpisodes.innerHTML = 'Show more';
			getMoreEpisodes.classList.add('get-more-episodes');
			episodeList.append(getMoreEpisodes);

		}

	}

	function resizeContainer(){
		const episodeListH = QS('.episode-list').clientHeight + 1000;
		QS('.container').style.height = episodeListH + 'px';
	}

	function restoreContainer(){
		QS('.container').style.height = 'auto';
	}

	function loadPodcasts(data){
		const podcastList = QS('.podcast-list');
		for (var i = 0; i < data.length; i++) {
			const podcastItem = document.createElement("div");
			const itemHeader = document.createElement("div");
			const itemImg = new Image();
			const podcastId = data[i].podcast_id;
			const podcastCover = data[i].cover;
			itemImg.alt = data[i].name + ' podcast cover image.';
			itemImg.src = 'http://data.localhost/art/' + podcastCover;
			podcastItem.classList.add('podcast-item');
			itemHeader.classList.add('item-header');
			itemHeader.innerHTML = data[i].name;
			podcastItem.append(itemHeader);
			podcastItem.append(itemImg);
			podcastList.append(podcastItem);
			podcastItem.onclick = () => {
				showEpisodes(podcastId, podcastCover);
			}
		}
	}



	const player = new Audio();
	const context = new AudioContext();
	let sinusD = startSinus();

	player.id = 'player';
	player.src = 'http://data.localhost/audio/?eid='+ localStorage.getItem('epid');
	player.preload = 'auto';
	player.type = 'audio/mpeg';
	player.crossOrigin = "*";

	player.onpause =()=>{
		context.suspend();
		sinusD = startSinus();
	}

	player.onplay =()=>{
		context.resume();
		clearInterval(sinusD);
	}

	function loadPlayer(eid, timecode){
		showPlayer();
		if(eid != localStorage.getItem('epid')){
				localStorage.setItem('epid', eid);
				console.log('load track ' + eid +' / '+ timecode);
				player.src = 'http://data.localhost/audio/?eid='+eid;
				player.preload = 'auto';
				player.type = 'audio/mpeg';
				player.crossOrigin = "*";
				player.currentTime = timecode;
				play(1);
		} else {
				player.currentTime = timecode;
				play(1);
		}
	}

	let source = context.createMediaElementSource(player);
	let analyser = context.createAnalyser();
	let gainNode = context.createGain();

	const frequencies = [60, 170, 310, 600, 1000, 3000, 6000, 8000, 14000, 16000];

	const eqPower = QS('.eq-switch');
	const visPower = QS('.vis-switch');
	const eqPowerKnob = QS('.eq-sw-position');
	const visPowerKnob = QS('.vis-sw-position');

	eqPower.onclick = () => {
		
		if (localStorage.getItem('processing') == 0) {
			localStorage.setItem('processing', 1);
			eqPowerKnob.classList.add('sw-enable');
		} else {
			eqPowerKnob.classList.remove('sw-enable');
			localStorage.setItem('processing', 0);
		}

		processingInit();

	}

	visPower.onclick = () => {

		if (localStorage.getItem('visualization') == 0) {
			localStorage.setItem('visualization', 1);
			visPowerKnob.classList.add('sw-enable');
			analyserContainer.style.display = 'block';
		} else {
			visPowerKnob.classList.remove('sw-enable');
			localStorage.setItem('visualization', 0);
			analyserContainer.style.display = 'none';
		}

		processingInit();

	}

	if (localStorage.getItem('processing') == null) {
			localStorage.setItem('processing', 1);
			eqPowerKnob.classList.add('sw-enable');
	} else {
		if (localStorage.getItem('processing') == 1) {
			eqPowerKnob.classList.add('sw-enable');
		}
	}

	if (localStorage.getItem('visualization') == null) {
			localStorage.setItem('visualization', 1);
			visPowerKnob.classList.add('sw-enable');
		} else {
			if (localStorage.getItem('visualization') == 1) {
				visPowerKnob.classList.add('sw-enable');
			}
		}

	function freqToText(f){
		let s = f.toString();
		if (f < 1000) {
			return s;
		} else {
			return s.slice(0, -3) + 'K';
		}
	}
	
	function eqFilter(freq){
		let filter = context.createBiquadFilter();
		filter.type = 'peaking'; // тип фильтра
		filter.frequency.value = freq; // частота
		filter.Q.value = 1; // Q-factor

		const eqRefreshInterval = setInterval(function(){
			if (localStorage.getItem(freq) == null) {
				localStorage.setItem(freq, 0);
			} else {
				filter.gain.value = localStorage.getItem(freq);
			}

		}, 1000);
		return filter;
	}

	function createEQ() {
		eq = frequencies.map(eqFilter);
		eq.reduce(function (prev, curr) {
			prev.connect(curr);
			return curr;
		});
		return eq;
	};

	let equalizer = createEQ();

	async function processingInit(){
		source.disconnect();
		equalizer[equalizer.length - 1].disconnect();
		analyser.disconnect();
		gainNode.disconnect();

		await delay(5).then(() => {
			if (localStorage.getItem('visualization') == 1) {
				if (localStorage.getItem('processing') == 1) {
					
					source.connect(analyser);
					analyser.connect(equalizer[0]);
					equalizer[equalizer.length - 1].connect(gainNode);
				} else {
					source.connect(analyser);
					analyser.connect(gainNode);
				}		
			} else {
				if (localStorage.getItem('processing') == 1) {
					source.connect(equalizer[0]);
					equalizer[equalizer.length - 1].connect(gainNode);
				} else {
					source.connect(gainNode);
				}
			}			
		});

		gainNode.connect(context.destination);
		
	}

	processingInit();
	
	
	const eqCont = QS('.equalizer-line');

	// Отрисовка блоков эквалайзера
	for (let x = 0; x < frequencies.length; x++) {
		let freq = frequencies[x];
		let divEB = document.createElement("div");
		let divEQP = document.createElement("div");
		let divEBZ = document.createElement("div");
		let divEBBT = document.createElement("div");
		divEB.classList.add('eq-bar');
		divEQP.classList.add('eq-position');
		divEBZ.classList.add('eq-bar-zone');
		divEBBT.classList.add('eq-bottom-text');
		divEBZ.dataset.frq = freq;
		divEB.append(divEBBT);
		divEB.append(divEQP);
		divEB.append(divEBZ);
		eqCont.append(divEB);
		divEBBT.innerHTML = freqToText(freq);
	
	}
	

	// Восстановление значений эквалайзера
	const eqBarsZn = QSA('.eq-bar-zone');
	const eqBars = QSA('.eq-bar');
	const eqPos = QSA('.eq-position');
	const eqValueText = QSA('.eq-bottom-text');

	for (let x = 0; x < frequencies.length; x++) {
		let val = localStorage.getItem(eqBarsZn[x].dataset.frq);
		eqPos[x].style.top = 50 - (val * 5) + 'px';
	}	

	
	// Обработка событий эквалайзера
	for (let x = 0; x < frequencies.length; x++) {
		eqBars[x].style.left = x * 35 + 'px';
		MEL(eqBarsZn[x], 'mousemove mouseup mousedown click', function(e){
			if(event.buttons === 1) {
				
				let frq = e.target.dataset.frq;
				let val = Math.round((50 - e.offsetY) / 5);
				e.target.closest('.eq-bar').children[1].style.top = 50 - (val * 5) + 'px';
				localStorage.setItem(frq, val);			
				eqValueText[x].innerHTML = val;

			}
		});

		MEL(eqBarsZn[x], 'mouseenter mousemove mousedown', function(e){
			e.target.closest('.eq-bar').children[1].classList.add('eq-position-hovered');
		});

		eqBarsZn[x].onmouseleave = async function(){
			await delay(500).then(() => {
				eqValueText[x].innerHTML = freqToText(frequencies[x]);
				eqPos[x].classList.remove('eq-position-hovered');

			});
		}
	}
	
	/* Анализатор спектра */
	analyser.fftSize = 2048;
	analyser.smoothingTimeConstant = 0.5;
	analyser.maxDecibels = -18;
	analyser.minDecibels = -92;
	const numBars = 15;
	const barSpacing = 7;
	const frequencyArray = new Uint8Array(analyser.frequencyBinCount);
	const frequencyMatrix = new Uint8Array(numBars+1);
	const frequencySet = [
			[2,3,0.78],		//0-60
			[4,5,0.89],		//60-80
			[6,7,0.98],		//100-190
			[10,12,0.99],	//190-300
			[15,16,1],		//300-400
			[18,20,1],		//400-500
			[23,33,1],		//500-800
			[33,62,1],		//800-1.49k
			[63,127,1],		//1.5-3k
			[130, 169,1],	//3k-4k
			[172,254,1],	//4k-6k
			[257,298,1],	//6k-7k
			[301,342,1.05],	//7k-8k
			[345,425,1.1],	//8k-10k
			[428,599,1.25], //10k-14k
			[602,853,1.3] 	//14k-20k
		];

	const analyserContainer = O('analyserContainer');
	const equalizerContainer = O('equalizerContainer');


	for (let i = 0; i <= numBars; i++) {
		let bar = document.createElement('div');
		bar.className = 'bar';
		analyserContainer.appendChild(bar);
	}

	const bars = QSA('.bar');

	

	function startSinus(){
		let _s = 0;
		const sinT = setInterval(function(){
		if((player.paused || player.ended)  && localStorage.getItem('visualization') == 1) {
			_s++;
			_s > 31 ? _s = 0 : null;
				for (let i = 0; i < bars.length; i++) {
					bars[i].classList.add('bar-smooth');
					bars[i].style.height = (Math.sin((_s / 5) - (i / 4)) * 15) + 145 / 9 + 'px';
					bars[i].style.left = i * barSpacing + 'px';
				}
			}
		}, 85);
		return sinT;
	}

	
	const analyserTimer = setInterval(function(){
		if(!player.paused && player.currentTime > 0 && !player.ended && localStorage.getItem('visualization') == 1) {
		
			analyser.getByteFrequencyData(frequencyArray);

			for (var i = 0; i <= numBars; i++) {
				frequencyMatrix[i] = Math.max.apply(null, frequencyArray.slice(frequencySet[i][0], frequencySet[i][1]));
			}

			let level = 0;
			
			for (var i = 0; i <= numBars; i++) {

				bars[i].classList.remove('bar-smooth');

				level = frequencyMatrix[i] / 7.5 * frequencySet[i][2];

				level < 1 ? level = 1 : null;
				level > 30 ? level = 30 : null;
				
				bars[i].style.height = level + 'px';
				bars[i].style.left = i * barSpacing + 'px';

			}

		}

	}, 1);

	/* Интерфейс плеера */

	const playerContainer = QS('.player-container');
	const seekBar = QS('.seek-bar');
	const seekPosition = QS('.seek-position');
	const eqButton = QS('.eq-button');
	const volButton = QS('.vol-button');
	const volBar = QS('.vol-bar');
	const volPosition = QS('.vol-position');
	const playPause = QS('.play-button');
	const nextButton = QS('.next-button');
	const prevButton = QS('.prev-button');
	const playTimer = QS('.play-timer');
	const episodeTitle = QS('.episode-title');
	const trackName = QS('.track-name');
	
	let unMuted = true;
	let notSeeking = true;

	if (localStorage.getItem('volume') == null) {
		localStorage.setItem('volume', 0.8);
	} 

	let startVolume = localStorage.getItem("volume");
	gainNode.gain.value = startVolume;
	updateVolumeIcon(startVolume);
	
	volPosition.style.width = gainNode.gain.value * 107 + 'px';
	
	player.onended = () => {
		playPause.classList.remove('pause-button');
	}
	
	// Управление с клавиатуры
	window.addEventListener("keydown", (e) => {
		if (e.keyCode == 37 || e.keyCode == 74) {
			prevTrack();
		}
		if (e.keyCode == 75 || e.keyCode == 32) {
			playPauseToggle();
			e.preventDefault();
			return false;
		}
		if (e.keyCode == 39 || e.keyCode == 76) {
			nextTrack();
		}
	});

	function showPlayer(){
		playerContainer.classList.remove('player-hidden');
	}

	function hidePlayer(){
		playerContainer.classList.add('player-hidden');
	}

	function stringXSlider(el){
		let pos = 0;
		let interval = setInterval(()=>{
			let range = el.offsetWidth - el.children[0].offsetWidth;
			if (range > 0) {
				el.children[0].style.left = 0;
				el.children[0].style.transitionDuration = '100ms';
				clearInterval(interval);
				return;
			}
			pos-=Math.abs(range);
			if (pos < range) {
				pos = 0;
			}
			el.children[0].style.left = pos + 'px';
			el.children[0].style.transitionDuration = '2s';
		}, 4000);
		
	}

	function updatePlayerInfo(_episode, _track){

		if (episodeTitle.children[0].innerHTML != _episode) {
			episodeTitle.children[0].innerHTML = _episode;
			stringXSlider(episodeTitle);
		}

		if (trackName.children[0].innerHTML != _track) {
			trackName.children[0].innerHTML = _track;
			stringXSlider(trackName);
		}

	}

	function getEpisodeName(currentEpisodeId){
		let episodes = JSON.parse(localStorage.getItem('episodes'));
		for (var i = episodes.length - 1; i >= 0; i--) {
			if (episodes[i].episode_id == currentEpisodeId) {
				return episodes[i].episode_name;
			}
		}
	}

	// Работа с таймкодами
	function updateTimeCodes(currentTime){

		let currentEpisode = localStorage.getItem('epid');

		let _trackList = QSA('.tracklist-item');
		let endTimecode = 0;
		let startTimecode = 0;



		for (var i = 0; i < _trackList.length; i++) {

			if (_trackList[i].dataset.episode_id == currentEpisode) {
		
				if (i < _trackList.length) {
					startTimecode = _trackList[i].dataset.timecode;
				}
				
				if (i < _trackList.length) {

					if (i == _trackList.length-1) {
						endTimecode = Math.round(player.duration);
					} else {
						endTimecode = _trackList[i+1].dataset.timecode;
					}
				}		

				if (currentTime > startTimecode && currentTime < endTimecode) {
					_trackList[i].classList.add('tracklist-item-playing');
					updatePlayerInfo(getEpisodeName(currentEpisode), _trackList[i].innerHTML);
				} else {
					_trackList[i].classList.remove('tracklist-item-playing');
				}

			}
		}

	


		
		if (player.paused) {
			player.currentTime = localStorage.getItem('time');
			
		}

		localStorage.setItem('time', player.currentTime);

	}

	// Таймер обновления таймкодов
	let playingTimer = setInterval(function(){

		updateTimeCodes(player.currentTime);

		let totalSec = Math.round(player.duration);
		let currentSec = Math.round(player.currentTime);

		if (notSeeking) {
			playTimer.innerHTML = secToStr(currentSec);
		}

		seekPosition.style.width = player.currentTime * seekBar.offsetWidth / player.duration + 'px';
		
	}, 1000);
	

	playPause.onclick = () => {
		playPauseToggle();
	}

	nextButton.onclick = () => {
		nextTrack();
	}

	prevButton.onclick = () => {
		prevTrack();
	}

	seekBar.onmousedown = () => {
		fadeOut();
	}

	seekBar.onmouseup = () => {
		delay(150).then(() => fadeIn());
	}

	seekBar.onclick = (e) => {
		changeSeekRaw(e);
	}

	seekBar.onmousemove = (e) => {
		displaySeekTime(e);		
	}

	seekBar.onmouseover = () => {
		notSeeking = false;
		playTimer.classList.add('in-seeking');
	}

	seekBar.onmouseleave = () => {
		notSeeking = true;
		playTimer.classList.remove('in-seeking');
		playTimer.innerHTML = secToStr(player.currentTime);
	}

	

	eqButton.onclick = () => {
		if (equalizerContainer.checkVisibility()) {
			eqButton.style.backgroundColor = '';
			equalizerContainer.style.display = 'none';
		} else {
			eqButton.style.backgroundColor = 'var(--main-accent-color)';
			equalizerContainer.style.display = 'block';			
	
			// equalizerContainer.onmouseleave = function(){
			// 	this.style.display = 'none';
			// 	this.onmouseleave = null;
			// }
			
		}
	}

	
	volButton.onclick = async() => {
		
		
		if (unMuted) {

			volButton.classList.add('vol-button-off');
			
			fadeOut();

			unMuted = false;

		} else {

			volButton.classList.remove('vol-button-off');

			fadeIn();

			unMuted = true;
		}	
	}

	function defaultGain(){
		return Math.round(localStorage.getItem('volume') * 30);
	}

	async function fadeIn(){
		for (let i = 0; i <= defaultGain(); i++) {
			await new Promise(resolve => setTimeout(resolve, 1));
			gainNode.gain.value = i / 30;
		}
	}
	async function fadeOut(){
		for (let i = defaultGain(); i >= 0; i--) {
			await new Promise(resolve => setTimeout(resolve, 1));
			gainNode.gain.value = i / 30;
		}
		return true;
	}

	async function play(){

		let playPromise = player.play();

			if (playPromise !== undefined) {
		    playPromise.then(_ => {	
		    	console.log('Play!');
		    	fadeIn();
		    })
		    .catch(error => {
		    		console.log('Waiting for play...');
					delay(5).then(() => play());
		    });
		}

		

		playPause.classList.add('pause-button');

	}

	async function pause(){
		fadeOut();
		playPause.classList.remove('pause-button');
		await delay(defaultGain() * 10).then(() => player.pause());
	}

	function playPauseToggle(){
		if(player.paused){
			play();
		} else {
			pause();			
		}
	}

	function nextTrack(){

		fadeOut();

		let atc = getAlltimecodes();

		delay(defaultGain() * 10).then(() => {
			for (let i = atc.length - 1; i >= 0; i--) {
				if (atc[i] > player.currentTime) {
						player.currentTime = atc[i];
					return;
				}
			}
		});
			
		delay(defaultGain() * 20).then(() => fadeIn());	
	}


	function prevTrack(){

		fadeOut();

		let atc = getAlltimecodes();

		delay(defaultGain() * 10).then(() => {
			for (let i = atc.length - 1; i >= 0; i--) {
				if (atc[i] > (player.currentTime - 3) && atc[i + 1] > 1) {
					player.currentTime = atc[i + 1];
					return;
				}
				if (i == 0) {
					player.currentTime = atc[1];
					return;
				}
			}
		});
		
		delay(defaultGain() * 20).then(() => fadeIn());
	}

	function getAlltimecodes(){
		let _pld = JSON.parse(localStorage.getItem('playlist'));
		let _atc = [];
		for (let i = 0; i < _pld.length; i++) {
			_atc.push(_pld[i].timecode)
		}
		return _atc;
	}

	function displaySeekTime(e){
		playTimer.innerHTML = secToStr(e.offsetX / seekBar.offsetWidth * player.duration);
	}

	function changeSeekRaw(e){
		player.currentTime = e.offsetX / seekBar.offsetWidth * player.duration;
		seekPosition.style.width = e.offsetX + 'px';
		localStorage.setItem('time', player.currentTime);
	}

	volBar.onmousemove = (e) => {
	    if(event.buttons === 1) {
	    	changeVolumeRaw(e);
	    }		
	}
	volBar.onclick = (e) => {
	    changeVolumeRaw(e);
	}
	volBar.onmousedown = (e) => {
	    changeVolumeRaw(e);
	}

	volBar.onwheel = (e) => {
		let max = 107;
		let cv = volPosition.clientWidth;
		if (e.deltaY > 0) {
			cv = cv - 5; 
		}
		if (e.deltaY < 0) {
			cv = cv + 5;
		}
		if (cv < 0) {
			cv = 0;
		}
		if (cv > 107) {
			cv = 107;
		}
		volPosition.style.width = cv + 'px';
		let v = cv / max * 1;
		gainNode.gain.value = v;
		updateVolumeIcon(v);
		localStorage.setItem("volume", v);
		return false;
	}

	function changeVolumeRaw(e){
		let volume = e.offsetX / volBar.offsetWidth * 1;
		if(e.offsetX <= volBar.offsetWidth && e.offsetX > 0) {
			volPosition.style.width = e.offsetX + 'px';
			if(volume < 0.02) {
				volume = 0;
			}
			if(volume > 0.97) {
				volume = 1;
			}
			updateVolumeIcon(volume)
			gainNode.gain.value = volume;
		}
		localStorage.setItem("volume", gainNode.gain.value);
	}

	function updateVolumeIcon(v){
		if (v == 0) {
			volButton.classList.add('vol-button-off');
		} else {
			volButton.classList.remove('vol-button-off');
		}
		if (v > 0.6) {
				volButton.classList.add('vol-button-up');
		} else {
			volButton.classList.remove('vol-button-up');
		}
		unMuted = true;
	}

	function secToStr(s) {
		!s ? s = 0 : s = Math.round(s);	
		let m = Math.trunc(s / 60) + '';
		let h = Math.trunc(m / 60) + '';
		s = (s % 60) + '';
		m = (m % 60) + '';
		return h.padStart(2, 0) + ':' +m.padStart(2, 0) + ':' + s.padStart(2, 0);
	}
	
	function O(i){ 
		return document.getElementById(i);
	}
	function QS(i){ 
		return document.querySelector(i);
	}
	function QSA(i){ 
		return document.querySelectorAll(i);
	}
	function MEL(element, eventNames, listener) {
		let events = eventNames.split(' ');
		for (let i=0, iLen=events.length; i<iLen; i++) {
			element.addEventListener(events[i], listener, false);
		}
		return element;
	}

	function delay(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
	
	async function request(url, data = []) {

		const response = await fetch(url+'/', {
			method: 'POST',
			mode: 'same-origin',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers: {'Content-Type': 'application/json; charset=utf-8'},
			redirect: 'error',
			referrerPolicy: 'no-referrer',
			body: JSON.stringify(data)
		});

		return await response.json();

	}


</script>

</body>
</html>